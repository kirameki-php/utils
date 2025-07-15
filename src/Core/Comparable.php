<?php declare(strict_types=1);

namespace Kirameki\Core;

interface Comparable
{
    /**
     * @param self $other
     * @return int<-1, 1>
     * Returns -1 if this object is less than `$other`.
     * Returns 0 if this object is equal to `$other`.
     * Returns 1 if this object is greater than `$other`.
     */
    public function compareTo(self $other): int;
}
