<?php declare(strict_types=1);

namespace Kirameki\Core;

use Kirameki\Core\Exceptions\InvalidArgumentException;
use function preg_match;
use function version_compare;

final class Version implements Comparable
{
    use Comparison;

    public static function parse(string $value): self
    {
        $self = self::parseOrNull($value);

        if ($self !== null) {
            return $self;
        }

        throw new InvalidArgumentException(
            "Invalid version format. Expected 'major.minor.patch'. Got '{$value}'.",
        );
    }

    /**
     * @param string $value
     * @return self|null
     */
    public static function tryParse(string $value): ?self
    {
        return preg_match('/^(\d+)\.(\d+)\.(\d+)$/', $value, $matches) === 1
            ? new self((int) $matches[1], (int) $matches[2], (int) $matches[3])
            : null;
    }

    /**
     * @return self
     */
    public static function zero(): self
    {
        return new self(0, 0, 0);
    }

    /**
     * @param int $major
     * @param int $minor
     * @param int $patch
     */
    public function __construct(
        public readonly int $major,
        public readonly int $minor,
        public readonly int $patch,
    ) {
    }

    /**
     * @return string
     */
    public function __toString(): string
    {
        return $this->toString();
    }

    /**
     * @return string
     */
    public function toString(): string
    {
        return sprintf('%d.%d.%d', $this->major, $this->minor, $this->patch);
    }

    /**
     * @param static $other
     * @return int
     */
    public function compareTo(Comparable $other): int
    {
        return version_compare($this->toString(), $other->toString());
    }

    /**
     * @param Version $from
     * @return bool
     */
    public function isMajorUpdate(self $from): bool
    {
        return $this->major > $from->major;
    }

    /**
     * @param Version $from
     * @return bool
     */
    public function isMinorUpdate(self $from): bool
    {
        return $this->major === $from->major
            && $this->minor > $from->minor;
    }

    /**
     * @param Version $from
     * @return bool
     */
    public function isPatchUpdate(self $from): bool
    {
        return $this->major === $from->major
            && $this->minor === $from->minor
            && $this->patch > $from->patch;
    }
}
