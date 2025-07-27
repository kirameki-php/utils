<?php

declare(strict_types=1);

namespace Kirameki\File;

enum FileSystemType: int
{
    case Unknown = 0000000;
    case Fifo = 0010000;
    case CharacterDevice = 0020000;
    case Directory = 0040000;
    case BlockDevice = 0060000;
    case File = 0100000;
    case Link = 0120000;
    case Socket = 0140000;
}
