<?php

declare(strict_types=1);

namespace JakubBoucek\Tail\Builder;

use JakubBoucek\Tail\Exceptions\InvalidArgumentException;
use JakubBoucek\Tail\Stream\ExternalStream;
use JakubBoucek\Tail\Stream\File;
use JakubBoucek\Tail\Stream\FileMode;
use JakubBoucek\Tail\Tail;

class Setup
{
    private Tail $tail;

    public function __construct(Tail $tail)
    {
        $this->tail = $tail;
    }

    public function fromFile(string $file): Input
    {
        return new Input($this->tail, new File($file, FileMode::Read));
    }

    public function fromStream($stream): Input
    {
        $input = new ExternalStream($stream);
        if (!$input->isSeekable()) {
            throw new InvalidArgumentException('Unable to use non-seekable stream type as input.');
        }
        return new Input($this->tail, $input);
    }
}
