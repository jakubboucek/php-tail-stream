<?php

declare(strict_types=1);

namespace JakubBoucek\Tail;

class SreamTail
{
    private int $blockSize = 4096;
    private string $rowDelimiter = "\n";

    /** @var resource */
    private $input;

    /** @var resource */
    private $output;

    public function __construct($input, $output)
    {
        if(!is_resource($input) || get_resource_type($input) !== 'stream') {
            throw new \InvalidArgumentException('Input must be a Stream resource.');
        }

        if(!(stream_get_meta_data($input)['seekable'] ?? false)) {
            throw new \InvalidArgumentException('Input stream must be seekable.');
        }

        $this->input = $input;

        if(!is_resource($output) || get_resource_type($output) !== 'stream') {
            throw new \InvalidArgumentException('Output must be a Stream resource.');
        }

        $this->output = $output;
    }

    public function tail(int $lines = 10): void
    {

    }

    public function getBlockSize(): int
    {
        return $this->blockSize;
    }

    public function setBlockSize(int $blockSize): SreamTail
    {
        if($blockSize < 1) {
            throw new \InvalidArgumentException('Buffer size must be greater than 0.');
        }

        $this->blockSize = $blockSize;
        return $this;
    }

    public function getRowDelimiter(): string
    {
        return $this->rowDelimiter;
    }

    public function setRowDelimiter(string $rowDelimiter): SreamTail
    {
        $len = strlen($rowDelimiter);
        if($len === 0) {
            throw new \InvalidArgumentException('Row delimiter must not be empty.');
        }
        if($len !== 1) {
            throw new \InvalidArgumentException(
                sprintf("Row delimiter can by only 1 character (byte), but %s given.", $len)
            );
        }

        $this->rowDelimiter = $rowDelimiter;
        return $this;
    }


}
