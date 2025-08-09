<?php

declare(strict_types=1);

namespace Kirameki\Storage;

use Kirameki\Core\Exceptions\RuntimeException;
use SplFileInfo;
use function lchgrp;
use function lchown;
use function unlink;

class Symlink extends Storable
{
    /**
     * @return Storable
     */
    public function getTarget(): Storable
    {
        $targetPath = $this->info->getLinkTarget();
        if ($targetPath === false) {
            throw new RuntimeException("Failed to read symlink: {$this->pathname}");
        }

        $info = new SplFileInfo($targetPath);

        return $info->isDir()
            ? new Directory($this->pathname, $info)
            : new File($this->pathname, $info);
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
}
