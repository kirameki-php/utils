<?php

declare(strict_types=1);

namespace Kirameki\FileSystem;

use Kirameki\Core\Exceptions\RuntimeException;
use Kirameki\Time\Instant;
use function dirname;
use function pathinfo;
use function posix_getpid;
use function time;
use function touch;
use const LOCK_EX;
use const PATHINFO_EXTENSION;

class File extends Storable
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

    /**
     * @var Directory 
     */
    public Directory $directory {
        get => $this->directory ??= new Directory(dirname($this->pathname));
    }

    /**
     * @return string
     */
    public function readContents(): string
    {
        $contents = file_get_contents($this->pathname);
        if ($contents === false) {
            throw new RuntimeException("Failed to read file: {$this->pathname}");
        }
        return $contents;
    }

    /**
     * @param string $contents
     * @param bool $lock
     * @return void
     */
    public function writeContents(string $contents, bool $lock = true): void
    {
        $flags = 0;

        if ($lock) {
            $flags |= LOCK_EX;
        }

        if (file_put_contents($this->pathname, $contents, $flags) === false) {
            throw new RuntimeException("Failed to write file: {$this->pathname}");
        }
    }

    /**
     * @param string $contents
     * @param string|null $tempFilePath
     * @return void
     */
    public function replaceContents(string $contents, ?string $tempFilePath = null): void
    {
        $tempFilePath ??= $this->pathname . '.tmp-' . time() . '-' . posix_getpid();
        $file = new File($tempFilePath);
        $file->copyTo($tempFilePath);
        $file->writeContents($contents, false);
        $file->moveTo($this->pathname);
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

    /**
     * @param Instant|null $mtime
     * @param Instant|null $ctime
     * @return void
     */
    public function touch(?Instant $mtime = null, ?Instant $ctime = null): void
    {
        touch($this->pathname, $mtime?->toInt(), $ctime?->toInt());
    }
}
