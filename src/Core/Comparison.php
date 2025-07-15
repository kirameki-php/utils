<?php declare(strict_types=1);

namespace Kirameki\Core;

trait Comparison
{
    /**
     * @inheritDoc
     */
    abstract public function compareTo(Comparable $other): int;

    /**
     * @param self $other
     * @return bool
     */
    public function isEqualTo(self $other): bool
    {
        return $this->compareTo($other) === 0;
    }

    /**
     * @param self $other
     * @return bool
     */
    public function isNotEqualTo(self $other): bool
    {
        return $this->compareTo($other) !== 0;
    }

    /**
     * @param self $other
     * @return bool
     */
    public function isLessThan(self $other): bool
    {
        return $this->compareTo($other) < 0;
    }

    /**
     * @param self $other
     * @return bool
     */
    public function isLessThanOrEqualTo(self $other): bool
    {
        return $this->compareTo($other) <= 0;
    }

    /**
     * @param self $other
     * @return bool
     */
    public function isGreaterThan(self $other): bool
    {
        return $this->compareTo($other) > 0;
    }

    /**
     * @param self $other
     * @return bool
     */
    public function isGreaterThanOrEqualTo(self $other): bool
    {
        return $this->compareTo($other) >= 0;
    }
}
