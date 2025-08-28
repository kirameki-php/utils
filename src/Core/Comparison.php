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
    public function isEqualTo(Comparable $other): bool
    {
        return $this->compareTo($other) === 0;
    }

    /**
     * @param self $other
     * @return bool
     */
    public function isNotEqualTo(Comparable $other): bool
    {
        return $this->compareTo($other) !== 0;
    }

    /**
     * @param self $other
     * @return bool
     */
    public function isLessThan(Comparable $other): bool
    {
        return $this->compareTo($other) < 0;
    }

    /**
     * @param self $other
     * @return bool
     */
    public function isLessThanOrEqualTo(Comparable $other): bool
    {
        return $this->compareTo($other) <= 0;
    }

    /**
     * @param self $other
     * @return bool
     */
    public function isGreaterThan(Comparable $other): bool
    {
        return $this->compareTo($other) > 0;
    }

    /**
     * @param self $other
     * @return bool
     */
    public function isGreaterThanOrEqualTo(Comparable $other): bool
    {
        return $this->compareTo($other) >= 0;
    }
}
