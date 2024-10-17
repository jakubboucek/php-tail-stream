<?php

declare(strict_types=1);

namespace JakubBoucek\Tail\Builder;

use JakubBoucek\Tail\Stream\ExternalStream;
use JakubBoucek\Tail\Stream\File;
use JakubBoucek\Tail\Stream\FileMode;
use JakubBoucek\Tail\Stream\Stream;
use JakubBoucek\Tail\Tail;

class Input
{

    private Stream $input;
    private Tail $tail;

    public function __construct(Tail $tail, Stream $input)
    {
        $this->input = $input;
        $this->tail = $tail;
    }

    public function toFile(string $file): void
    {
        $this->tail->lines(10, $this->input, new File($file, FileMode::Write));
    }

    public function toStream($stream): Output
    {
        return new Output($this->tail, new ExternalStream($stream));
    }
}
