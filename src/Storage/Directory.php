<?php

declare(strict_types=1);

namespace Kirameki\Storage;

use FilesystemIterator;
use GlobIterator;
use Iterator;
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

class Directory extends Storable
{
    /**
     * @return Vec<covariant Storable>
     */
    public function getFiles(bool $followSymlinks = true): Vec
    {
        $flags = FilesystemIterator::CURRENT_AS_PATHNAME
               | FilesystemIterator::SKIP_DOTS;

        $iterator = new FilesystemIterator($this->pathname, $flags);

        return $this->iterateFiles($iterator, $followSymlinks);
    }

    /**
     * @return Vec<covariant Storable>
     */
    public function getFilesRecursively(bool $followSymlinks = true): Vec
    {
        $flags = FilesystemIterator::CURRENT_AS_PATHNAME
               | FilesystemIterator::SKIP_DOTS;

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
     * @param string $pattern
     * @param bool $followSymlinks
     * @return Vec<covariant Storable>
     */
    public function glob(string $pattern, bool $followSymlinks = true): Vec
    {
        $flags = FilesystemIterator::CURRENT_AS_PATHNAME
               | FilesystemIterator::SKIP_DOTS;

        $iterator = new GlobIterator("{$this->pathname}/{$pattern}", $flags);

        return $this->iterateFiles($iterator, $followSymlinks);
    }


    /**
     * @param Iterator<string> $iterator
     * @return Vec<covariant Storable>
     */
    protected function iterateFiles(Iterator $iterator, bool $followSymlinks): Vec
    {
        $storables = [];
        foreach ($iterator as $pathname) {
            $storables[] = match (true) {
                !$followSymlinks && is_link($pathname) => new Symlink($pathname),
                is_dir($pathname) => new Directory($pathname),
                default => new File($pathname),
            };
            clearstatcache(false, $pathname);
        };

        return new Vec($storables);
    }

    /**
     * @param string $name
     * @param int $permissions
     * @param bool $created
     * @param-out bool $created
     * @return Directory
     */
    public function createSubDirectory(string $name, int $permissions, bool &$created = false): Directory
    {
        $dirPath = $this->pathname . '/' . $name;
        $exists = is_dir($dirPath);

        if (!$exists) {
            mkdir($dirPath, $permissions, true);
            $created = true;
        } else {
            clearstatcache(false, $dirPath);
        }

        return new Directory($dirPath);
    }

    /**
     * @param string $name
     * @param string $contents
     * @return File
     */
    public function createFile(string $name, string $contents): File
    {
        $filePath = $this->pathname . '/' . $name;

        if (file_put_contents($filePath, $contents) === false) {
            throw new RuntimeException("Failed to create file: {$filePath}");
        }

        return new File($filePath);
    }

    /**
     * @inheritDoc
     */
    public function delete(): void
    {
        $flags = FilesystemIterator::CURRENT_AS_PATHNAME
               | FilesystemIterator::SKIP_DOTS;

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($this->pathname, $flags),
            RecursiveIteratorIterator::CHILD_FIRST,
        );

        foreach ($iterator as $pathname) {
            is_dir($pathname)
                ? rmdir($pathname)
                : unlink($pathname);
        }
        rmdir($this->pathname);
    }
}
