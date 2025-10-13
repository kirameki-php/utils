<?php

declare(strict_types=1);

namespace Kirameki\Storage;

use SplFileInfo;
use Stringable;
use function array_filter;
use function array_pop;
use function explode;
use function implode;

final class Path implements Stringable
{
    /**
     * Combines multiple paths into one, ensuring there are no duplicate slashes.
     *
     * @param string ...$paths
     * @return string
     */
    public static function combine(string ...$paths): string
    {
        $filtered = array_filter($paths, fn($p) => $p !== '');
        return preg_replace('#/+#', '/', implode('/', $filtered)) ?: '';
    }

    public static function of(string $path): self
    {
        return new self($path);
    }

    /**
     * @param string $value
     */
    protected function __construct(
        protected string $value,
    )
    {
    }

    /**
     * @return bool
     */
    public function isAbsolute(): bool
    {
        return str_starts_with($this->value, '/');
    }

    /**
     * @return bool
     */
    public function isRelative(): bool
    {
        return !$this->isAbsolute();
    }

    /**
     * @param string $suffix
     * @return bool
     */
    public function endsWith(string $suffix): bool
    {
        return str_ends_with($this->value, $suffix);
    }

    /**
     * Splits the path into its segments.
     *
     * @param bool $normalize Whether to normalize the segments by resolving `.` and `..`.
     * @return array<int, string>
     */
    public function segments(bool $normalize = true): array
    {
        $parts = $normalize
            ? $this->normalizeSegments(true)
            : explode('/', $this->value);

        $result = [];
        foreach ($parts as $part) {
            if ($part !== '') {
                $result[] = $part;
            }
        }
        return $result;
    }

    /**
     * Normalizes a path by resolving `.` and `..` segments and removing redundant slashes.
     *
     * @return string
     */
    public function normalize(): string
    {
        return implode('/', $this->normalizeSegments(true));
    }

    /**
     * Normalizes an array of path segments by resolving `.` and `..` segments and removing redundant slashes.
     *
     * @return list<string>
     */
    protected function normalizeSegments(bool $preserveLeadingSlash = false): array
    {
        $result = [];
        foreach (explode('/', $this->value) as $i => $part) {
            match ($part) {
                '' => ($preserveLeadingSlash && $i === 0) ? $result[] = $part : null,
                '.' => null,
                '..' => array_pop($result),
                default => $result[] = $part,
            };
        }
        return $result;
    }

    /**
     * @param string $subpath
     * @return $this
     */
    public function append(string|self $subpath): self
    {
        $subpath = ($subpath instanceof self) ? $subpath->value : $subpath;
        $this->value = self::combine($this->value, $subpath);
        return $this;
    }

    /**
     * Converts the path to a Storable instance (File, Directory, or Symlink).
     *
     * @param bool $followSymlink
     * Whether to follow symlinks when determining the type.
     * @return Storable
     */
    public function toStorable(bool $followSymlink = true): Storable
    {
        return Storable::fromInfo(new SplFileInfo($this->value), $followSymlink);
    }

    /**
     * @return string
     */
    public function __toString(): string
    {
        return $this->value;
    }

    /**
     * @return string
     */
    public function toString(): string
    {
        return $this->__toString();
    }
}
