<?php

declare(strict_types=1);

namespace Kirameki\File;

use Kirameki\Core\Exceptions\RuntimeException;
use Kirameki\Time\Instant;
use function dirname;
use function pathinfo;
use function touch;
use const PATHINFO_EXTENSION;

class FileInfo extends FileSystemInfo
{
    /**
     * @var string
     */
    public string $extension {
        get => pathinfo($this->pathname, PATHINFO_EXTENSION);
    }

    /**
     * @var int
     */
    public int $bytes {
        get => $this->stat('size');
    }

    public DirectoryInfo $directory {
        get => $this->directory ??= new DirectoryInfo(dirname($this->pathname));
    }

    /**
     * @return string
     */
    public function getContents(): string
    {
        $contents = file_get_contents($this->pathname);
        if ($contents === false) {
            throw new RuntimeException("Failed to read file: {$this->pathname}");
        }
        return $contents;
    }

    public function putContent(string $contents): void
    {
        if (file_put_contents($this->pathname, $contents) === false) {
            throw new RuntimeException("Failed to write file: {$this->pathname}");
        }
    }

    /**
     * @param Instant|null $mtime
     * @param Instant|null $ctime
     * @return void
     */
    public function touch(?Instant $mtime = null, ?Instant $ctime = null): void
    {
        touch($this->pathname, $mtime?->toInt(), $ctime?->toInt());
    }

    /**
     * @inheritDoc
     */
    public function delete(): void
    {
        if (!unlink($this->pathname)) {
            throw new RuntimeException("Failed to delete file: {$this->pathname}");
        }
    }
}
