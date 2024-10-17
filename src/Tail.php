<?php

declare(strict_types=1);

namespace JakubBoucek\Tail;

use JakubBoucek\Tail\Builder\Setup;
use JakubBoucek\Tail\Stream\Stream;

class Tail
{
    private int $blockSize = 4096;
    private string $rowDelimiter = "\n";
    private int $count;
    private Units $units;

    /**
     * @param Units $units (unised - for future use)
     */
    public function __construct(int $count = 10, Units $units = Units::Lines)
    {
        $this->count = $count;
        $this->units = $units;
    }

    public static function lines(int $count): Setup
    {
        return new Setup(new Tail($count, Units::Lines));
    }

    public function process(Stream $input, Stream $output): void
    {
        $processor = new Processor();
        match ($this->units) {
            Units::Lines => $processor->lines($this->count, $input, $output, $this->blockSize, $this->rowDelimiter),
            //Units::Bytes => $processor->bytes($this->count, $input, $output, $this->bufferSize),
        };
    }
}
