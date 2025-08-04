<?php declare(strict_types=1);

namespace Kirameki\Stream;

interface StreamLockable extends Streamable
{
    /**
     * @param bool $blocking
     * @return bool
     */
    function exclusiveLock(bool $blocking = true): bool;

    /**
     * @param bool $blocking
     * @return bool
     */
    function sharedLock(bool $blocking = true): bool;

    /**
     * @return bool
     */
    function unlock(): bool;
}
