<?php

declare(strict_types=1);

namespace JakubBoucek\Tail\Stream;

use JakubBoucek\Tail\Exceptions\InvalidArgumentException;
use JakubBoucek\Tail\Exceptions\InvalidStateException;

class ExternalStream implements Stream
{
    /** @var resource */
    private $stream;
    private bool $isUsed = false;

    /** @param $stream resource */
    public function __construct($stream)
    {
        $resourceType = get_resource_type($stream);
        if (!is_resource($stream) || $resourceType !== 'stream') {
            throw new InvalidArgumentException('Stream must be a Stream resource, got .');
        }

        $this->stream = $stream;
    }

    public function isSeekable(): bool
    {
        return stream_get_meta_data($this->stream)['seekable'] ?? false;
    }

    /**
     * @return resource
     */
    public function open()
    {
        if ($this->isUsed) {
            throw new InvalidStateException('Unable to re-open already used Stream, it can be used once only.');
        }

        $this->isUsed = true;
        return $this->stream;
    }

    public function close(): void
    {
        // do nothing - do not touch the
    }
}
