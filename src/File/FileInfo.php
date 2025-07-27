<?php

declare(strict_types=1);

namespace Kirameki\File;

use function pathinfo;
use const PATHINFO_EXTENSION;

class FileInfo extends FileSystemInfo
{
    /**
     * @return string
     */
    public function extension(): string
    {
        return pathinfo($this->relativePath, PATHINFO_EXTENSION);
    }

    /**
     * @return int
     */
    public function bytes(): int
    {
        return $this->stat()['size'];
    }

    public function linkCount(): int
    {
        return $this->stat()['nlink'];
    }
}
