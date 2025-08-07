<?php

declare(strict_types=1);

namespace Kirameki\Storage;

use Kirameki\Core\Exceptions\RuntimeException;
use Kirameki\Time\Instant;
use function array_slice;
use function basename;
use function chgrp;
use function chmod;
use function chown;
use function clearstatcache;
use function copy;
use function decoct;
use function dirname;
use function file_exists;
use function is_executable;
use function is_readable;
use function is_writable;
use function pathinfo;
use function realpath;
use function rename;
use function stat;
use const PATHINFO_FILENAME;

abstract class Storable
{
    /**
     * @var string
     */
    public string $filename {
        get => pathinfo($this->pathname, PATHINFO_FILENAME);
    }

    /**
     * @var FileType
     */
    public FileType $type {
        get => FileType::from($this->stat('mode') &- 0o7777);
    }

    /**
     * @var int
     */
    public int $permissions {
        get => $this->stat('mode') & 0o7777;
    }

    /**
     * @var int
     */
    public int $uid {
        get => $this->stat('uid');
    }

    /**
     * @var int
     */
    public int $gid {
        get => $this->stat('gid');
    }

    /**
     * @var Instant
     */
    public Instant $atime {
        get => new Instant($this->stat('atime'));
    }

    /**
     * @var Instant
     */
    public Instant $mtime {
        get => new Instant($this->stat('mtime'));
    }

    /**
     * @var Instant
     */
    public Instant $ctime {
        get => new Instant($this->stat('ctime'));
    }

    /**
     * @param string $pathname
     * @param array<int|string, int>|null $stat
     */
    public function __construct(
        public readonly string $pathname,
        protected ?array $stat = null,
    ) {
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
    public function realpath(): string
    {
        $path = realpath($this->pathname);
        if ($path === false) {
            throw new RuntimeException("Failed to resolve real path for {$this->pathname}");
        }
        clearstatcache(true, $path);
        return $path;
    }

    /**
     * @return bool
     */
    public function isWritable(): bool
    {
        $writable = is_writable($this->pathname);
        clearstatcache(false, $this->pathname);
        return $writable;
    }

    /**
     * @return bool
     */
    public function isReadable(): bool
    {
        $readable = is_readable($this->pathname);
        clearstatcache(false, $this->pathname);
        return $readable;
    }

    /**
     * @return bool
     */
    public function isExecutable(): bool
    {
        $executable = is_executable($this->pathname);
        clearstatcache(false, $this->pathname);
        return $executable;
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

    # region File Metadata ---------------------------------------------------------------------------------------------

    /**
     * @param string $key
     * @return int
     */
    public function stat(string $key): int
    {
        $this->stat ??= $this->resolveStat();
        return $this->stat[$key] ?? throw new RuntimeException("Stat key '{$key}' does not exist.");
    }

    /**
     * @return array<int|string, int>
     */
    protected function resolveStat(): array
    {
        $stat = $this->callStatCommand();
        if ($stat === false) {
            throw new RuntimeException("Failed to retrieve file stat for {$this->pathname}", [
                'path' => $this->pathname,
            ]);
        }
        $sliced = array_slice($stat, 13, null, true);
        clearstatcache(false, $this->pathname);
        return $sliced;
    }

    /**
     * @return array<int|string, int>|false
     */
    protected function callStatCommand(): array|false
    {
        return stat($this->pathname);
    }

    # endregion File Metadata ------------------------------------------------------------------------------------------
}
