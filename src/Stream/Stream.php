<?php

declare(strict_types=1);

namespace JakubBoucek\Tail\Stream;

interface Stream
{
    /** @return resource */
    public function open();

    public function close(): void;
}
