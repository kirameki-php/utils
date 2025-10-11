<?php

declare(strict_types=1);

namespace Kirameki\Storage;

use Kirameki\Time\Instant;
use SplFileInfo;
use function basename;
use function chgrp;
use function chmod;
use function chown;
use function clearstatcache;
use function dirname;
use function file_exists;
use function rename;

abstract class Storable
{
    /**
     * @var string
     */
    public protected(set) string $pathname;

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
     * @var FileType
     */
    public FileType $type {
        get => $this->type ??= $this->resolveType();
    }

    /**
     * @var Directory
     */
    public Directory $directory {
        get => $this->directory ??= $this->resolveDirectory();
    }

    /**
     * @var SplFileInfo
     */
    protected SplFileInfo $info;

    /**
     * @param SplFileInfo $info
     * @param bool $followSymlink
     * @return Directory|File|Symlink
     */
    public static function fromInfo(SplFileInfo $info, bool $followSymlink = true): self
    {
        if ($followSymlink) {
            return ($info->isDir())
                ? new Directory($info->getPathname(), $info)
                : new File($info->getPathname(), $info);
        }

        return match ($info->getType()) {
            'link' => new Symlink($info->getPathname(), $info),
            'dir' => new Directory($info->getPathname(), $info),
            default => new File($info->getPathname(), $info),
        };
    }

    /**
     * @param string $pathname
     * @param SplFileInfo|null $info
     */
    public function __construct(
        string $pathname,
        ?SplFileInfo $info = null,
    ) {
        $this->pathname = $pathname;
        $this->info = $info ?? $this->newFileInfo();
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
        chmod($this->pathname, $permissions);
        $this->info = $this->newFileInfo();
    }

    /**
     * @param int|string $uid
     * @param int|string|null $gid
     * @return void
     */
    public function chown(int|string $uid, int|string|null $gid = null): void
    {
        chown($this->pathname, $uid);
        if ($gid !== null) {
            chgrp($this->pathname, $gid);
        }
        $this->info = $this->newFileInfo();
    }

    /**
     * @param int|string $gid
     * @return void
     */
    public function chgrp(int|string $gid): void
    {
        chgrp($this->pathname, $gid);
        $this->info = $this->newFileInfo();
    }

    /**
     * @param string $destination
     * @return void
     */
    public function moveTo(string $destination): void
    {
        rename($this->pathname, $destination);
        $this->pathname = $destination;
        $this->directory = $this->resolveDirectory();
        $this->info = $this->newFileInfo();
    }

    /**
     * @return void
     */
    abstract public function delete(): void;

    # endregion Operations ---------------------------------------------------------------------------------------------

    protected function resolveType(): FileType
    {
        return FileType::from($this->info->getType());
    }

    /**
     * @return Directory
     */
    protected function resolveDirectory(): Directory
    {
        return new Directory(dirname($this->pathname));
    }

    /**
     * @return SplFileInfo
     */
    protected function newFileInfo(): SplFileInfo
    {
        return new SplFileInfo($this->pathname);
    }
}
