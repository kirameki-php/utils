<?php

declare(strict_types=1);

namespace Kirameki\Storage;

use FilesystemIterator;
use Generator;
use Kirameki\Collections\Vec;
use Kirameki\Core\Exceptions\RuntimeException;
use RecursiveDirectoryIterator;
use RecursiveIterator;
use RecursiveIteratorIterator;
use function clearstatcache;
use function file_put_contents;
use function is_dir;
use function mkdir;
use function rmdir;
use function unlink;

class Directory extends Storable
{
    /**
     * @param bool $followSymlinks
     * @return ($followSymlinks is true ? Vec<Directory|File> : Vec<Directory|File|Symlink>)
     */
    public function scan(bool $followSymlinks = true): Vec
    {
        $flags = FilesystemIterator::SKIP_DOTS;
        if (!$followSymlinks) {
            $flags |= FilesystemIterator::FOLLOW_SYMLINKS;
        }

        $iterator = new FilesystemIterator($this->pathname, $flags);
        return new Vec($this->iterate($iterator, $followSymlinks));
    }

    /**
     * @param bool $followSymlinks
     * @return ($followSymlinks is true ? Vec<Directory|File> : Vec<Directory|File|Symlink>)
     */
    public function scanRecursively(bool $followSymlinks = true): Vec
    {
        $flags = FilesystemIterator::SKIP_DOTS;
        if (!$followSymlinks) {
            $flags |= FilesystemIterator::FOLLOW_SYMLINKS;
        }

        $iterator = new RecursiveDirectoryIterator($this->pathname, $flags);
        return new Vec($this->iterate($iterator, $followSymlinks));
    }

    /**
     * @param FilesystemIterator $iterator
     * @param bool $followSymlinks
     * @return ($followSymlinks is true ? Generator<Directory|File> : Generator<Directory|File|Symlink>)
     */
    protected function iterate(iterable $iterator, bool $followSymlinks): Generator
    {
        if ($iterator instanceof RecursiveIterator) {
            $iterator = new RecursiveIteratorIterator($iterator);
        }

        foreach ($iterator as $info) {
            yield Storable::fromInfo($info, $followSymlinks);
        }
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
        file_put_contents($filePath, $contents);
        return new File($filePath);
    }

    /**
     * @inheritDoc
     */
    public function delete(): void
    {
        $flags = FilesystemIterator::SKIP_DOTS;

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($this->pathname, $flags),
            RecursiveIteratorIterator::CHILD_FIRST,
        );

        foreach ($iterator as $pathname => $info) {
            $info->getType() === 'dir'
                ? rmdir($pathname)
                : unlink($pathname);
        }
        rmdir($this->pathname);
    }
}
