<?php

declare(strict_types=1);

namespace Kirameki\Storage;

enum FileType: string
{
    case File = 'file';
    case Link = 'link';
    case Directory = 'dir';
    case BlockDevice = 'block';
    case Fifo = 'fifo';
    case CharacterDevice = 'char';
    case Socket = 'socket';
    case Unknown = 'unknown';
}
