<?php

declare(strict_types=1);

namespace JakubBoucek\Tail\Stream;

enum FileMode: string
{
    case Read = 'rb';
    case Write = 'wb';
}
