<?php

declare(strict_types=1);

namespace Kirameki\FileSystem;

use Kirameki\Core\Exceptions\RuntimeException;
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
