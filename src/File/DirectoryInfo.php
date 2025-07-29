<?php

declare(strict_types=1);

namespace Kirameki\File;

use FilesystemIterator;
use Iterator;
use Kirameki\Collections\LazyIterator;
use Kirameki\Collections\Vec;
use Kirameki\Core\Exceptions\RuntimeException;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use function clearstatcache;
use function file_put_contents;
use function is_dir;
use function is_link;
use function mkdir;
use function rmdir;
use function unlink;

class DirectoryInfo extends FileSystemInfo
{
    /**
     * @return Vec<FileSystemInfo>
     */
    public function getFiles(): Vec
    {
        $flags = 0;
        $flags |= FilesystemIterator::CURRENT_AS_PATHNAME;
        $flags |= FilesystemIterator::SKIP_DOTS;

        $iterator = new FilesystemIterator($this->pathname, $flags);

        return $this->iterateFiles($iterator, true);
    }

    /**
     * @return Vec<FileSystemInfo>
     */
    public function getFilesRecursively(bool $followSymlinks = true): Vec
    {
        $flags = 0;
        $flags |= FilesystemIterator::CURRENT_AS_PATHNAME;
        $flags |= FilesystemIterator::SKIP_DOTS;
        if ($followSymlinks) {
            $flags |= FilesystemIterator::FOLLOW_SYMLINKS;
        }

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($this->pathname, $flags),
            RecursiveIteratorIterator::LEAVES_ONLY,
        );

        return $this->iterateFiles($iterator, $followSymlinks);
    }

    /**
     * @param Iterator<string> $iterator
     * @return Vec<FileSystemInfo>
     */
    protected function iterateFiles(Iterator $iterator, bool $followSymlinks): Vec
    {
        return new Vec(new LazyIterator(
            (static function () use ($iterator, $followSymlinks) {
                foreach ($iterator as $pathname) {
                    yield match (true) {
                        !$followSymlinks && is_link($pathname) => new SymlinkInfo($pathname),
                        is_dir($pathname) => new DirectoryInfo($pathname),
                        default => new FileInfo($pathname),
                    };
                }
            })(),
        ));
    }

    /**
     * @param string $name
     * @param int $permissions
     * @return DirectoryInfo
     */
    public function createSubDirectory(string $name, int $permissions): DirectoryInfo
    {
        $dirPath = $this->pathname . '/' . $name;

        if (!is_dir($dirPath)) {
            mkdir($dirPath, $permissions, true);
        }

        return new DirectoryInfo($dirPath);
    }

    /**
     * @param string $name
     * @param string $contents
     * @return FileInfo
     */
    public function createFile(string $name, string $contents): FileInfo
    {
        $filePath = $this->pathname . '/' . $name;

        if (file_put_contents($filePath, $contents) === false) {
            throw new RuntimeException("Failed to create file: {$filePath}");
        }

        return new FileInfo($filePath);
    }

    /**
     * @inheritDoc
     */
    public function delete(): void
    {
        $flags = 0;
        $flags |= FilesystemIterator::CURRENT_AS_PATHNAME;
        $flags |= FilesystemIterator::SKIP_DOTS;

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($this->pathname, $flags),
            RecursiveIteratorIterator::CHILD_FIRST,
        );

        foreach ($iterator as $pathname) {
            is_dir($pathname)
                ? rmdir($pathname)
                : unlink($pathname);
        }
    }
}
