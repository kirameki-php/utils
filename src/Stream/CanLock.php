<?php declare(strict_types=1);

namespace Kirameki\Stream;

use Closure;
use function flock;
use const LOCK_EX;
use const LOCK_NB;
use const LOCK_SH;
use const LOCK_UN;

trait CanLock
{
    /**
     * @return resource
     */
    abstract public function getResource(): mixed;

    /**
     * @param bool $blocking
     * @return bool
     */
    public function exclusiveLock(bool $blocking = true): bool
    {
        return flock(
            $this->getResource(),
            $blocking ? LOCK_EX : LOCK_EX | LOCK_NB
        );
    }

    /**
     * @template TReturn
     * @param Closure(static): TReturn $call
     * @return TReturn
     */
    public function withExclusiveLock(Closure $call): mixed
    {
        try {
            $this->exclusiveLock();
            return $call($this);
        } finally {
            $this->unlock();
        }
    }

    /**
     * @param bool $blocking
     * @return bool
     */
    public function sharedLock(bool $blocking = true): bool
    {
        return flock(
            $this->getResource(),
            $blocking ? LOCK_SH : LOCK_SH | LOCK_NB
        );
    }

    /**
     * @template TReturn
     * @param Closure(static): TReturn $call
     * @return TReturn
     */
    public function withSharedLock(Closure $call): mixed
    {
        try {
            $this->sharedLock();
            return $call($this);
        }
        finally {
            $this->unlock();
        }
    }

    /**
     * @return bool
     */
    public function unlock(): bool
    {
        return flock($this->getResource(), LOCK_UN);
    }
}
