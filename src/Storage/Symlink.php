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

        $info = new SplFileInfo($targetPath);

        return $info->isDir()
            ? new Directory($targetPath, $info)
            : new File($targetPath, $info);
    }

    /**
     * @inheritDoc
     */
    public function delete(): void
    {
        unlink($this->pathname);
    }
}
