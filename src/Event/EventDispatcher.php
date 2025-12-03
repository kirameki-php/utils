<?php declare(strict_types=1);

namespace Kirameki\Event;

use function array_key_exists;

class EventDispatcher implements EventEmitter
{
    /**
     * @var array<class-string<Event>, EventHandler<Event>>
     */
    protected array $handlers = [];

    /**
     * @template TEvent of Event
     * @param TEvent $event
     * @return int<0, max>
     */
    public function emit(Event $event, bool &$wasCanceled = false): int
    {
        return $this->hasListeners($event::class)
            ? $this->get($event::class)->emit($event, $wasCanceled)
            : 0;
    }

    /**
     * @template TEvent of Event
     * @param class-string<TEvent> $name
     * @return bool
     */
    protected function hasListeners(string $name): bool
    {
        return (bool) $this->getOrNull($name)?->hasListeners();
    }

    /**
     * @template TEvent of Event
     * @param class-string<TEvent> $name
     * @return EventHandler<TEvent>
     */
    protected function get(string $name): EventHandler
    {
        /** @var EventHandler<TEvent> */
        return $this->handlers[$name] ??= new EventHandler($name);
    }

    /**
     * @template TEvent of Event
     * @param class-string<TEvent> $name
     * @return EventHandler<TEvent>|null
     */
    protected function getOrNull(string $name): ?EventHandler
    {
        /** @var EventHandler<TEvent>|null */
        return $this->handlers[$name] ?? null;
    }

    /**
     * @template TEvent of Event
     * @param class-string<TEvent> $name
     * @return bool
     */
    protected function remove(string $name): bool
    {
        if (array_key_exists($name, $this->handlers)) {
            unset($this->handlers[$name]);
            return true;
        }
        return false;
    }
}
