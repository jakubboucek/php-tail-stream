<?php

declare(strict_types=1);

namespace JakubBoucek\Tail\Stream;

use JakubBoucek\Tail\Exceptions\InvalidArgumentException;
use JakubBoucek\Tail\Exceptions\InvalidStateException;
use JakubBoucek\Tail\Exceptions\IOException;

class File implements Stream
{
    private string $filename;
    private FileMode $mode;

    /** @var null|resource */
    private $stream;
    private bool $isUsed = false;

    public function __construct(string $filename, FileMode $mode)
    {
        $this->filename = $filename;
        $this->mode = $mode;
    }

    /**
     * @return resource
     */
    public function open()
    {
        if ($this->isUsed) {
            throw new InvalidStateException('Unable to re-open already used File, it can be used once only.');
        }
        $this->isUsed = true;

        $stream = fopen($this->filename, $this->mode->value);

        if (!$stream) {
            $error = error_get_last()['message'] ?? 'Unknown error';
            throw new IOException(
                sprintf("Unable to open file '%s' for '%s' mode (%s).", $this->filename, $this->mode->name, $error)
            );
        }

        $this->stream = $stream;

        return $this->stream;
    }

    public function close(): void
    {
        if (!isset($this->stream)) {
            return;
        }

        fclose($this->stream);
        unset($this->stream);
    }
}
