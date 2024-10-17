<?php

declare(strict_types=1);

namespace JakubBoucek\Tail;

use JakubBoucek\Tail\Exceptions\IOException;
use JakubBoucek\Tail\Stream\Stream;
use LogicException;

class Processor
{

    public function lines(
        int $count,
        Stream $inputStream,
        Stream $outputStream,
        int $blocksSizes,
        string $delimiter
    ): void {
        $input = $inputStream->open();

        $this->seekInputForLines($count, $input, $blocksSizes, $delimiter);

        $output = $outputStream->open();

        stream_copy_to_stream($input, $output);

        $inputStream->close();
        $outputStream->close();
    }

    /**
     * @param resource $input
     */
    private function seekInputForLines(
        int $count,
        $input,
        int $blocksSizes,
        string $delimiter
    ): void {
        $this->seek($input, 0, SEEK_END);
        $inputPosition = ftell($input);

        // 0 or false
        if (!($inputPosition > 0)) {
            return;
        }

        $inputOoccurrences = 0;

        do {
            $blockSize = min($blocksSizes, $inputPosition);
            $inputPosition -= $blockSize;
            $this->seek($input, $inputPosition);
            $block = fread($input, $blockSize);

            if ($block === false) {
                throw new IOException('Unable to read from the input stream.');
            }

            [$occurrences, $blockPosition] = $this->searchInBlock($block, $delimiter, $count - $inputOoccurrences);

            $inputOoccurrences += $occurrences;

            // Not-null $inputPosition means we found enough lines, $blockPosition contains expected position
            // in block cointext, add it to $inputPosition to get absolute position in input
            if ($blockPosition !== null) {
                $inputPosition += $blockPosition;
                $this->seek($input, $inputPosition);
                return;
            }
        } while ($inputPosition > 0);

        // Last resort: whole input does not contain enough lines, just seek to the beginning for output whole input
        $this->seek($input, 0);
    }

    private function searchInBlock(string $block, string $delimiter, int $limit): array
    {
        // Fast forward: Count occurrences, if not enough, fast return for skip to next block
        $occurrences = substr_count($block, $delimiter);
        if ($occurrences < $limit) {
            // Return occurrences, but no position because we need to read more blocks
            return [$occurrences, null];
        }

        // Slow way: Repeat search for the expected occurrence
        $blockLen = strlen($block);
        $occurrences = 0;
        // Misleading initial value – it's for simple computation of the first `$offset`
        $position = $blockLen;

        do {
            // Negative offset is tricky - skip 1 byte = `-2`, etc.,
            // but offset must not be greater (in absolute value) than block length.
            $offset = -min($blockLen, 1 + $blockLen - $position);

            $position = strrpos($block, $delimiter, $offset);

            // Nothing found, this should not happen, because `substr_count()` promised enough occurrences
            if ($position === false) {
                throw new LogicException('Search algorithm failed: lack of occurrences despite the promise.');
            }
        } while (++$occurrences < $limit);

        // Yes, we found exact number of occurrences
        return [$occurrences, $position];
    }

    private function seek($resource, int $position, int $whence = SEEK_SET): void
    {
        if (fseek($resource, $position, $whence) === -1) {
            throw new IOException('Unable to seek in the input stream.');
        }
    }
}
