<?php declare(strict_types=1);

namespace Kirameki\Event;

abstract class Event
{
    /**
     * @var bool
     */
    protected bool $evictCallback = false;

    /**
     * @var bool
     */
    protected bool $cancelled = false;

    /**
     * Mark signal callback for removal.
     * When this is set to **true**, the signal callback will be removed.
     *
     * @return $this
     */
    public function evictCallback(bool $toggle = true): static
    {
        $this->evictCallback = $toggle;
        return $this;
    }

    /**
     * Returns whether the signal callback should be removed.
     *
     * @return bool
     */
    public function willEvictCallback(): bool
    {
        return $this->evictCallback;
    }

    /**
     * @return void
     */
    public function cancel(): void
    {
        $this->cancelled = true;
    }

    /**
     * @return bool
     */
    public function isCancelled(): bool
    {
        return $this->cancelled;
    }

    /**
     * Resets all changes after passing it to each callback.
     *
     * @internal
     * This is called by the EventHandler after each emit.
     * Do not call from user land.
     *
     * @return void
     */
    public function resetAfterCall(): void
    {
        $this->evictCallback(false);
        $this->cancelled = false;
    }
}
