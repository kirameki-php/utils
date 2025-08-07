<?php

declare(strict_types=1);

namespace Kirameki\Storage;

use Kirameki\Core\Exceptions\RuntimeException;
use Kirameki\Stream\FileStream;
use Kirameki\Time\Instant;
use function dirname;
use function file_get_contents;
use function file_put_contents;
use function posix_getpid;
use function time;
use function touch;
use function unlink;
use const LOCK_EX;

class File extends Storable
{
    /**
     * @var string
     */
    public string $extension {
        get => $this->info->getExtension();
    }

    /**
     * @var int
     */
    public int $bytes {
        get => $this->info->getSize();
    }

    /**
     * @var Directory
     */
    public Directory $directory {
        get => $this->directory ??= new Directory(dirname($this->pathname));
    }

    /**
     * @param string $mode
     * @return FileStream
     */
    public function open(string $mode = 'c+b'): FileStream
    {
        return new FileStream($this->pathname, $mode);
    }

    /**
     * @return string
     */
    public function read(): string
    {
        $contents = file_get_contents($this->pathname);
        if ($contents === false) {
            throw new RuntimeException("Failed to read file: {$this->pathname}");
        }
        return $contents;
    }

    /**
     * @param string $data
     * @param bool $lock
     * @return void
     */
    public function write(string $data, bool $lock = true): void
    {
        $flags = 0;

        if ($lock) {
            $flags |= LOCK_EX;
        }

        if (file_put_contents($this->pathname, $data, $flags) === false) {
            throw new RuntimeException("Failed to write file: {$this->pathname}");
        }
    }

    /**
     * @param string $data
     * @param string|null $tempFilePath
     * @return void
     */
    public function replace(string $data, ?string $tempFilePath = null): void
    {
        $tempFilePath ??= $this->pathname . '.tmp-' . time() . '-' . posix_getpid();
        $tempFile = new File($tempFilePath);
        $tempFile->write($data, false);
        $tempFile->chmod($this->permissions);
        $tempFile->chown($this->uid, $this->gid);
        $tempFile->moveTo($this->pathname);
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
