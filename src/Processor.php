<?php

declare(strict_types=1);

namespace JakubBoucek\Tail;

use JakubBoucek\Tail\Exceptions\IOException;
use JakubBoucek\Tail\Stream\Stream;
use LogicException;
use Traversable;

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

        [$start, $end] = $this->searchLines($count, $input, $blocksSizes, $delimiter);
        $length = $end - $start;

        $output = $outputStream->open();

        $this->seek($input, $start);
        stream_copy_to_stream($input, $output, $length);

        $inputStream->close();
        $outputStream->close();
    }

    /**
     * @param int $count Count if lines
     * @param Stream $inputStream Input stream
     * @param int $blocksSizes Size of blocks to read during backward search of lines - affects consumed memory
     * @param int $maxLineSize Size of maximal size of line - if line is longer, it will be split
     * @param string $delimiter Delimiter of lines
     * @return Traversable<string> Searched lines as string
     */
    public function linesIterator(
        int $count,
        Stream $inputStream,
        int $blocksSizes,
        int $maxLineSize,
        string $delimiter
    ): Traversable {
        $input = $inputStream->open();

        [$start, $end] = $this->searchLines($count, $input, $blocksSizes, $delimiter);

        $this->seek($input, $start);

        do {
            $line = stream_get_line($input, $maxLineSize, $delimiter);
            if ($line === false) {
                break;
            }
            yield $line;
        } while (ftell($input) < $end);

        $inputStream->close();
    }

    /**
     * @param resource $input
     */
    private function searchLines(
        int $count,
        $input,
        int $blocksSizes,
        string $delimiter
    ): array {
        $this->seek($input, 0, SEEK_END);
        $start = $end = ftell($input);

        // 0 or false
        if (!($start > 0)) {
            return [$start, $end];
        }

        $loop = 0;
        $occurrences = 0;

        do {
            $size = min($blocksSizes, $start);
            $start -= $size;
            $this->seek($input, $start);
            $block = fread($input, $size);

            if ($block === false || $block === '') {
                throw new IOException('Unable to read from the input stream.');
            }

            // Ignore last delimiter (trailing line) at the end of input
            if ($loop++ === 0 && substr($block, -1) === $delimiter) {
                $end--;
                $block = substr($block, 0, -1);
            }

            [$blockOccurrences, $blockPosition] = $this->searchInBlock($block, $delimiter, $count - $occurrences);

            $occurrences += $blockOccurrences;

            // Not-null $blockPosition means we found enough lines, $blockPosition contains expected position
            // in block cointext, add it to $inputPosition to get absolute position in input
            if ($blockPosition !== null) {
                $start += $blockPosition;
                return [$start + 1, $end];
            }
        } while ($start > 0);

        // Last resort: whole input does not contain enough lines, just seek to the beginning for output whole input
        return [0, $end];
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
