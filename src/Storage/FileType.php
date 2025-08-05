<?php

declare(strict_types=1);

namespace Kirameki\Storage;

enum FileType: int
{
    case Unknown = 0o0000000;
    case Fifo = 0o0010000;
    case CharacterDevice = 0o0020000;
    case Directory = 0o0040000;
    case BlockDevice = 0o0060000;
    case File = 0o0100000;
    case Link = 0o0120000;
    case Socket = 0o0140000;
}
