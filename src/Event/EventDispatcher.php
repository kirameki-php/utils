<?php declare(strict_types=1);

namespace Kirameki\Event;

use Closure;
use Kirameki\Event\Listeners\CallbackListener;
use Kirameki\Event\Listeners\CallbackOnceListener;
use Kirameki\Event\Listeners\EventListener;
use Override;

class EventDispatcher implements EventEmitter
{
    /**
     * @var array<class-string<Event>, EventHandler<Event>>
     */
    protected array $handlers = [];

    /**
     * @var list<Closure(Event, int): mixed>
     */
    protected array $onEmitted = [];

    /**
     * @inheritDoc
     */
    #[Override]
    public function emit(Event $event, bool &$wasCanceled = false): int
    {
        $count = 0;
        if ($handler = $this->getOrNull($event::class)) {
            $count = $handler->emit($event, $wasCanceled);

            if ($handler->hasNoListeners()) {
                $this->remove($event::class);
            }
        }

        foreach ($this->onEmitted as $callback) {
            $callback($event, $count);
        }

        return $count;
    }

    /**
     * Appends a listener to the beginning of the list for the given event.
     * This method must have an Event as the first parameter.
     *
     * This method is useful and cleaner than using append() but is slower since
     * it needs to extract the event class name from the callback using reflections.
     *
     * @template TEvent of Event
     * @param class-string<TEvent> $name
     * @param Closure(TEvent): mixed $callback
     * @return CallbackListener<TEvent>
     */
    public function on(string $name, Closure $callback): CallbackListener
    {
        return $this->get($name)->do($callback);
    }

    /**
     * Appends a listener to the beginning of the list for the given event.
     * This method must have an Event as the first parameter. Listener will be
     * removed after it's called once.
     *
     * @template TEvent of Event
     * @param class-string<TEvent> $name
     * @param Closure(TEvent): mixed $callback
     * @return CallbackOnceListener<TEvent>
     */
    public function once(string $name, Closure $callback): CallbackOnceListener
    {
        return $this->get($name)->doOnce($callback);
    }

    /**
     * @template TEvent of Event
     * @param class-string<TEvent> $name
     * @return EventHandler<TEvent>
     */
    public function get(string $name): EventHandler
    {
        /** @var EventHandler<TEvent> */
        return $this->handlers[$name] ??= new EventHandler($name);
    }

    /**
     * @template TEvent of Event
     * @param class-string<TEvent> $name
     * @return EventHandler<TEvent>|null
     */
    public function getOrNull(string $name): ?EventHandler
    {
        /** @var EventHandler<TEvent>|null */
        return $this->handlers[$name] ?? null;
    }

    /**
     * Remove all listeners for the given event.
     *
     * @template TEvent of Event
     * @param class-string<TEvent> $name
     * @return int<-1, max>
     * The number of listeners that were removed.
     * Returns -1 if the event handler does not exist.
     */
    public function remove(string $name): int
    {
        if ($handler = $this->getOrNull($name)) {
            $count = $handler->removeAllListeners();
            unset($this->handlers[$name]);
            return $count;
        }
        return -1;
    }

    /**
     * @template TEvent of Event
     * @param class-string<TEvent> $name
     * @return bool
     */
    public function hasListeners(string $name): bool
    {
        return (bool) $this->getOrNull($name)?->hasListeners();
    }

    /**
     * Removes the given callback from the listeners of the given event.
     * Returns the number of listeners that were removed.
     *
     * @template TEvent of Event
     * @param EventListener<TEvent> $listener
     * @return int<0, max>
     */
    public function removeListener(EventListener $listener): int
    {
        $count = 0;

        $class = $listener->eventClass;
        $handler = $this->getOrNull($class);
        if ($handler === null) {
            return $count;
        }

        $count += $handler->removeListener($listener);

        if (!$handler->hasListeners()) {
            $this->remove($class);
        }

        return $count;
    }

    /**
     * Registers a callback that will be invoked whenever an event is emitted.
     * Used for logging or debugging purposes.
     *
     * @param Closure(Event, int): mixed $callback
     * First parameter is the emitted event.
     * Second parameter is the number of listeners that were invoked.
     * @return void
     */
    public function onEmitted(Closure $callback): void
    {
        $this->onEmitted[] = $callback;
    }
}
