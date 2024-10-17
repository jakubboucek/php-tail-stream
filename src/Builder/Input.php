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
        $this->tail->process($this->input, new File($file, FileMode::Write));
    }

    public function toStream($stream): void
    {
        $this->tail->process($this->input, new ExternalStream($stream));
    }

    public function toOutput(): void
    {
        $output = defined('STDOUT')
            ? new ExternalStream(STDOUT)
            : new File('php://output', FileMode::Write);

        $this->tail->process($this->input, $output);
    }
}
