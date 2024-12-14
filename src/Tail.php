<?php

declare(strict_types=1);

namespace JakubBoucek\Tail;

use JakubBoucek\Tail\Builder\Setup;
use JakubBoucek\Tail\Stream\Stream;
use Traversable;

class Tail
{
    public const DefaultMaxLineSize = 2 ** 20;

    private int $blockSize = 4096;
    private string $rowDelimiter = "\n";
    private int $count;

    public function __construct(int $count = 10)
    {
        $this->count = $count;
    }

    public static function lines(int $count): Setup
    {
        return new Setup(new Tail($count));
    }

    public function processStream(Stream $input, Stream $output): void
    {
        $processor = new Processor();
        $processor->lines($this->count, $input, $output, $this->blockSize, $this->rowDelimiter);
    }

    public function processIterator(Stream $input, int $maxLineSize = self::DefaultMaxLineSize): Traversable
    {
        $processor = new Processor();
        return $processor->linesIterator($this->count, $input, $this->blockSize, $maxLineSize, $this->rowDelimiter);
    }
}
