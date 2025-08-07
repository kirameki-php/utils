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
     * @return Vec<covariant Storable>
     */
    public function scan(bool $followSymlinks = true): Vec
    {
        $flags = FilesystemIterator::SKIP_DOTS;
        $iterator = new FilesystemIterator($this->pathname, $flags);

        return $this->iterate($iterator, $followSymlinks);
    }

    /**
     * @return Vec<covariant Storable>
     */
    public function getFilesRecursively(bool $followSymlinks = true): Vec
    {
        $flags = FilesystemIterator::SKIP_DOTS;
        if ($followSymlinks) {
            $flags |= FilesystemIterator::FOLLOW_SYMLINKS;
        }

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($this->pathname, $flags),
            RecursiveIteratorIterator::LEAVES_ONLY,
        );

        return $this->iterate($iterator, $followSymlinks);
    }

    /**
     * @return Vec<Directory>
     */
    public function getDirectoryRecursively(bool $followSymlinks = true): Vec
    {
        $flags = FilesystemIterator::SKIP_DOTS;
        if ($followSymlinks) {
            $flags |= FilesystemIterator::FOLLOW_SYMLINKS;
        }

        // $info->isDir() returns true for directories and symlinks to directories
        // So if $followSymlinks is false, we need to check the type explicitly
        // by calling $info->getType() and checking if it equals 'dir'.
        $checker = $followSymlinks
            ? static fn(SplFileInfo $info) => $info->isDir()
            : static fn(SplFileInfo $info) => $info->getType() === 'dir';

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($this->pathname, $flags),
            RecursiveIteratorIterator::SELF_FIRST,
        );

        $directories = [];
        foreach ($iterator as $pathname => $info) {
            if ($checker($info)) {
                $directories[] = new Directory($pathname, $info);
            }
        }
        return new Vec($directories);
    }

    /**
     * @param Iterator<string, SplFileInfo> $iterator
     * @return Vec<covariant Storable>
     */
    protected function iterate(Iterator $iterator, bool $followSymlinks): Vec
    {
        $storables = [];
        foreach ($iterator as $pathname => $info) {
            $storables[] = match ($info->getType()) {
                'dir' => new Directory($pathname, $info),
                'link' => $this->resolveLink($pathname, $info, $followSymlinks),
                default => new File($pathname, $info),
            };
        }

        return new Vec($storables);
    }

    protected function resolveLink(string $pathname, SplFileInfo $info, bool $followLink): Storable
    {
        if (!$followLink) {
            return new Symlink($pathname, $info);
        }

        return $info->isDir()
            ? new Directory($pathname, $info)
            : new File($pathname, $info);
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
