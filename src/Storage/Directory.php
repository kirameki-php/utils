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
     * @return Vec<covariant Storable>
     */
    public function scan(bool $followSymlinks = true): Vec
    {
        $storables = [];
        foreach (new FilesystemIterator($this->pathname) as $pathname => $info) {
            if ($info instanceof SplFileInfo) {
                $storables[] = match ($info->getType()) {
                    'dir' => new Directory($pathname, $info),
                    'link' => $this->resolveLink($pathname, $info, $followSymlinks),
                    default => new File($pathname, $info),
                };
            }
        }
        return new Vec($storables);
    }

    /**
     * @param string $pathname
     * @param SplFileInfo $info
     * @param bool $followSymlinks
     * @return Storable
     */
    protected function resolveLink(string $pathname, SplFileInfo $info, bool $followSymlinks): Storable
    {
        if (!$followSymlinks) {
            return new Symlink($pathname, $info);
        }

        return $info->isDir()
            ? new Directory($pathname, $info)
            : new File($pathname, $info);
    }

    /**
     * @param bool $followSymlinks
     * @return ($followSymlinks is true ? Vec<File> : Vec<File|Symlink>)
     */
    public function getFilesRecursively(bool $followSymlinks = true): Vec
    {
        $iterator = $this->instantiateDirectoryIterator($followSymlinks);
        $iteratorIterator = new RecursiveIteratorIterator($iterator);
        return new Vec($this->iterateFiles($iteratorIterator, $followSymlinks));
    }

    /**
     * @param bool $followSymlinks
     * @return ($followSymlinks is true ? Vec<Directory> : Vec<Directory|Symlink>)
     */
    public function getDirectoryRecursively(bool $followSymlinks = true): Vec
    {
        $iterator = $this->instantiateDirectoryIterator($followSymlinks);
        $iteratorIterator = new RecursiveIteratorIterator($iterator, RecursiveIteratorIterator::SELF_FIRST);
        return new Vec($this->iterateDirectories($iteratorIterator, $followSymlinks));
    }

    /**
     * @param bool $followSymlinks
     * @return RecursiveDirectoryIterator
     */
    protected function instantiateDirectoryIterator(bool $followSymlinks): RecursiveDirectoryIterator
    {
        $flags = FilesystemIterator::SKIP_DOTS;
        if ($followSymlinks) {
            $flags |= FilesystemIterator::FOLLOW_SYMLINKS;
        }
        return new RecursiveDirectoryIterator($this->pathname, $flags);
    }

    /**
     * @param iterable<string, SplFileInfo> $iterator
     * @param bool $followSymlinks
     * @return ($followSymlinks is true ? Generator<File> : Generator<File|Symlink>)
     */
    protected function iterateFiles(iterable $iterator, bool $followSymlinks): Generator
    {
        foreach ($iterator as $pathname => $info) {
            if ($info->isFile()) {
                yield !$followSymlinks && $info->isLink()
                    ? new Symlink($pathname, $info)
                    : new File($pathname, $info);
            }
        }
    }

    /**
     * @param iterable<string, SplFileInfo> $iterator
     * @param bool $followSymlinks
     * @return ($followSymlinks is true ? Generator<Directory> : Generator<Directory|Symlink>)
     */
    protected function iterateDirectories(iterable $iterator, bool $followSymlinks): Generator
    {
        foreach ($iterator as $pathname => $info) {
            if ($info->isDir()) {
                yield !$followSymlinks && $info->isLink()
                    ? new Symlink($pathname, $info)
                    : new Directory($pathname, $info);
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
