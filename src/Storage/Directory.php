<?php

declare(strict_types=1);

namespace Kirameki\Storage;

use FilesystemIterator;
use Generator;
use Kirameki\Collections\Vec;
use Kirameki\Core\Exceptions\RuntimeException;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;
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
        return new Vec($this->iterate($followSymlinks));
    }

    /**
     * @param bool $followSymlinks
     * @return ($followSymlinks is true ? Vec<Directory|File> : Vec<Directory|File|Symlink>)
     */
    public function scanRecursively(bool $followSymlinks = true): Vec
    {
        return new Vec($this->iterateRecursive($followSymlinks));
    }

    /**
     * @param bool $followSymlinks
     * @return ($followSymlinks is true ? Generator<Directory|File> : Generator<Directory|File|Symlink>)
     */
    protected function iterate(bool $followSymlinks): Generator
    {
        $flags = FilesystemIterator::SKIP_DOTS;
        $flags |= FilesystemIterator::CURRENT_AS_FILEINFO;

        if (!$followSymlinks) {
            $flags |= FilesystemIterator::FOLLOW_SYMLINKS;
        }

        foreach (new FilesystemIterator($this->pathname, $flags) as $info) {
            /** @var SplFileInfo $info */
            yield Storable::fromInfo($info, $followSymlinks);
        }
    }

    protected function iterateRecursive(
        bool $followSymlinks,
        int $maxDepth = 10,
        int $currentDepth = 0,
    ): Generator
    {
        if ($currentDepth > $maxDepth) {
            throw new RuntimeException("Maximum directory recursion depth of {$maxDepth} exceeded.");
        }

        foreach ($this->iterate($followSymlinks) as $storable) {
            if ($storable instanceof Directory) {
                foreach ($storable->iterateRecursive($followSymlinks, $maxDepth, $currentDepth + 1) as $child) {
                    yield $child;
                }
            } else {
                yield $storable;
            }
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
