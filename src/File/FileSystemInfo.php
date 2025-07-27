<?php

declare(strict_types=1);

namespace Kirameki\File;

use Kirameki\Core\Exceptions\RuntimeException;
use Kirameki\Time\Instant;
use function basename;
use function clearstatcache;
use function dirname;
use function lstat;
use function pathinfo;

abstract class FileSystemInfo
{
    /**
     * @param string $relativePath
     * @param array{
     *      dev: int,
     *      ino: int,
     *      mode: int,
     *      nlink: int,
     *      uid: int,
     *      gid: int,
     *      rdev: int,
     *      size: int,
     *      atime: int,
     *      mtime: int,
     *      ctime: int,
     *      blksize: int,
     *      blocks: int,
     *  }|null $stat
     */
    public function __construct(
        public readonly string $relativePath,
        public ?array $stat = null,
    ) {
    }

    /**
     * @param string $suffix
     * @return string
     */
    public function basename(string $suffix = ''): string
    {
        return basename($this->relativePath, $suffix);
    }

    /**
     * @param int<1, max> $levels
     * @return string
     */
    public function dirname(int $levels = 1): string
    {
        return dirname($this->relativePath, $levels);
    }

    /**
     * @return string
     */
    public function filename(): string
    {
        return pathinfo($this->relativePath, PATHINFO_FILENAME);
    }

    # region File Metadata ---------------------------------------------------------------------------------------------

    /**
     * @return FileSystemType
     */
    public function type(): FileSystemType
    {
        return FileSystemType::from($this->stat()['mode'] &- 0o7777);
    }

    public function permission(): FilePermission
    {
        return new FilePermission($this->stat()['mode'] & 0o7777);
    }

    /**
     * @return int
     */
    public function uid(): int
    {
        return $this->stat()['uid'];
    }

    /**
     * @return int
     */
    public function gid(): int
    {
        return $this->stat()['gid'];
    }

    /**
     * @return Instant
     */
    public function atime(): Instant
    {
        return new Instant($this->stat()['atime']);
    }

    /**
     * @return Instant
     */
    public function mtime(): Instant
    {
        return new Instant($this->stat()['mtime']);
    }

    /**
     * @return Instant
     */
    public function ctime(): Instant
    {
        return new Instant($this->stat()['ctime']);
    }

    /**
     * @return array{
     *     dev: int,
     *     ino: int,
     *     mode: int,
     *     nlink: int,
     *     uid: int,
     *     gid: int,
     *     rdev: int,
     *     size: int,
     *     atime: int,
     *     mtime: int,
     *     ctime: int,
     *     blksize: int,
     *     blocks: int,
     * }
     */
    public function stat(): array
    {
        if ($this->stat === null) {
            if (($stat = lstat($this->relativePath)) === false) {
                throw new RuntimeException("Failed to retrieve file stat for {$this->relativePath}", [
                    'path' => $this->relativePath,
                ]);
            }
            $this->stat = $stat;
            clearstatcache();
        }
        return $this->stat;
    }

    # endregion File Metadata ------------------------------------------------------------------------------------------
}
