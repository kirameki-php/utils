<?php

declare(strict_types=1);

namespace Kirameki\File;

use Kirameki\Core\Exceptions\RuntimeException;
use function clearstatcache;
use function is_dir;
use function lchgrp;
use function lchown;
use function lstat;
use function readlink;

class SymlinkInfo extends FileInfo
{
    /**
     * @return FileSystemInfo
     */
    public function getTarget(): FileSystemInfo
    {
        $targetPath = readlink($this->pathname);
        if ($targetPath === false) {
            throw new RuntimeException("Failed to read symlink: {$this->pathname}");
        }

        $isDir = is_dir($targetPath);
        clearstatcache(false, $targetPath);

        return $isDir
            ? new DirectoryInfo($this->pathname)
            : new FileInfo($this->pathname);
    }

    /**
     * @inheritDoc
     */
    public function chown(int|string $uid, int|string|null $gid = null): void
    {
        if (!lchown($this->pathname, $uid)) {
            throw new RuntimeException("Failed to change ownership for {$this->pathname} to UID: {$uid}, GID: {$gid}");
        }

        if ($gid !== null) {
            $this->chgrp($gid);
        }
    }

    /**
     * @inheritDoc
     */
    public function chgrp(int|string $gid): void
    {
        if (!lchgrp($this->pathname, $gid)) {
            throw new RuntimeException("Failed to change group for {$this->pathname} to GID: {$gid}");
        }
    }

    /**
     * @inheritDoc
     */
    protected function callStat(): array|false
    {
        return lstat($this->pathname);
    }
}
