<?php declare(strict_types=1);

namespace Kirameki\Event;

interface EventEmitter
{
    /**
     * Calls all listeners for the given event.
     *
     * @template TEvent of Event
     * @param TEvent $event
     * @param bool $wasCanceled
     * @param-out bool $wasCanceled
     * @return int<0, max>
     */
    public function emit(Event $event, bool &$wasCanceled = false): int;
}
