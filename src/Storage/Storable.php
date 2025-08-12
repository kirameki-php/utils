<?php

declare(strict_types=1);

namespace Kirameki\Storage;

use Kirameki\Core\Exceptions\RuntimeException;
use Kirameki\Time\Instant;
use SplFileInfo;
use function basename;
use function chgrp;
use function chmod;
use function chown;
use function clearstatcache;
use function copy;
use function decoct;
use function dirname;
use function file_exists;
use function rename;

abstract class Storable
{
    /**
     * @var Directory
     */
    public Directory $directory {
        get => $this->directory ??= new Directory(dirname($this->pathname));
    }

    /**
     * @var FileType
     */
    public FileType $type {
        get => $this->type ??= FileType::from($this->info->getType());
    }

    /**
     * @var string
     */
    public string $name {
        get => $this->basename('.' . $this->extension);
    }

    /**
     * @var string
     */
    public string $filename {
        get => $this->info->getFilename();
    }

    /**
     * @var string
     */
    public string $extension {
        get => $this->info->getExtension();
    }

    /**
     * @var int
     */
    public int $permissions {
        get => $this->info->getPerms() & 0777;
    }

    /**
     * @var int
     */
    public int $uid {
        get => $this->info->getOwner();
    }

    /**
     * @var int
     */
    public int $gid {
        get => $this->info->getGroup();
    }

    /**
     * @var int
     */
    public int $bytes {
        get => $this->info->getSize();
    }

    /**
     * @var Instant
     */
    public Instant $atime {
        get => new Instant($this->info->getATime());
    }

    /**
     * @var Instant
     */
    public Instant $mtime {
        get => new Instant($this->info->getMTime());
    }

    /**
     * @var Instant
     */
    public Instant $ctime {
        get => new Instant($this->info->getCTime());
    }

    /**
     * @var SplFileInfo
     */
    protected SplFileInfo $info;

    /**
     * @param string $pathname
     * @param SplFileInfo|null $info
     */
    public function __construct(
        public readonly string $pathname,
        ?SplFileInfo $info = null,
    ) {
        $this->info = $info ?? new SplFileInfo($pathname);
    }

    /**
     * @param string $suffix
     * @return string
     */
    public function basename(string $suffix = ''): string
    {
        return basename($this->pathname, $suffix);
    }

    /**
     * @param int<1, max> $levels
     * @return string
     */
    public function dirname(int $levels = 1): string
    {
        return dirname($this->pathname, $levels);
    }

    /**
     * @return string
     */
    public function realPath(): string
    {
        return $this->info->getRealPath();
    }

    /**
     * @return bool
     */
    public function isWritable(): bool
    {
        return $this->info->isWritable();
    }

    /**
     * @return bool
     */
    public function isReadable(): bool
    {
        return $this->info->isReadable();
    }

    /**
     * @return bool
     */
    public function isExecutable(): bool
    {
        return $this->info->isExecutable();
    }

    /**
     * @return bool
     */
    public function isLink(): bool
    {
        return $this->info->isLink();
    }

    # region Operations ------------------------------------------------------------------------------------------------

    /**
     * @return bool
     */
    public function exists(): bool
    {
        $exists = file_exists($this->pathname);
        clearstatcache(false, $this->pathname);
        return $exists;
    }

    /**
     * @param int $permissions
     * @return void
     */
    public function chmod(int $permissions): void
    {
        if (!chmod($this->pathname, $permissions)) {
            throw new RuntimeException("Failed to change permissions for {$this->pathname} to " . decoct($permissions));
        }
    }

    /**
     * @param int|string $uid
     * @param int|string|null $gid
     * @return void
     */
    public function chown(int|string $uid, int|string|null $gid = null): void
    {
        if (!$this->callChownCommand($uid)) {
            throw new RuntimeException("Failed to change ownership for {$this->pathname} to UID: {$uid}, GID: {$gid}");
        }

        if ($gid !== null) {
            $this->chgrp($gid);
        }
    }

    /**
     * @param int|string $uid
     * @return bool
     */
    protected function callChownCommand(int|string $uid): bool
    {
        return chown($this->pathname, $uid);
    }

    /**
     * @param int|string $gid
     * @return void
     */
    public function chgrp(int|string $gid): void
    {
        if (!$this->callChGrpCommand($gid)) {
            throw new RuntimeException("Failed to change group for {$this->pathname} to GID: {$gid}");
        }
    }

    /**
     * @param int|string $gid
     * @return bool
     */
    protected function callChGrpCommand(int|string $gid): bool
    {
        return chgrp($this->pathname, $gid);
    }

    /**
     * @param string $destination
     * @return void
     */
    public function copyTo(string $destination): void
    {
        if (!copy($this->pathname, $destination)) {
            throw new RuntimeException("Failed to copy file from {$this->pathname} to {$destination}");
        }
    }

    /**
     * @param string $destination
     * @return void
     */
    public function moveTo(string $destination): void
    {
        if (!rename($this->pathname, $destination)) {
            throw new RuntimeException("Failed to move directory from {$this->pathname} to {$destination}");
        }
    }

    /**
     * @return void
     */
    abstract public function delete(): void;

    # endregion Operations ---------------------------------------------------------------------------------------------
}
