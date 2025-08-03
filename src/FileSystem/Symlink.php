<?php

declare(strict_types=1);

namespace Kirameki\FileSystem;

use Kirameki\Core\Exceptions\RuntimeException;
use function chown;
use function clearstatcache;
use function is_dir;
use function lchgrp;
use function lchown;
use function lstat;
use function readlink;

class Symlink extends File
{
    /**
     * @return Storable
     */
    public function getTarget(): Storable
    {
        $targetPath = readlink($this->pathname);
        if ($targetPath === false) {
            throw new RuntimeException("Failed to read symlink: {$this->pathname}");
        }

        $isDir = is_dir($targetPath);
        clearstatcache(false, $targetPath);

        return $isDir
            ? new Directory($this->pathname)
            : new File($this->pathname);
    }

    /**
     * @inheritDoc
     */
    protected function callChownCommand(int|string $uid): bool
    {
        return lchown($this->pathname, $uid);
    }

    /**
     * @inheritDoc
     */
    protected function callChGrpCommand(int|string $gid): bool
    {
        return lchgrp($this->pathname, $gid);
    }

    /**
     * @inheritDoc
     */
    protected function callStatCommand(): array|false
    {
        return lstat($this->pathname);
    }
}
