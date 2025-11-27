<?php declare(strict_types=1);

namespace Kirameki\Collections;

use Closure;
use Countable;
use IteratorAggregate;
use Kirameki\Collections\Utils\Arr;
use Override;
use Traversable;
use function count;
use function is_countable;

/**
 * @template TKey of array-key
 * @template TValue
 * @implements IteratorAggregate<TKey, TValue>
 */
abstract class Enumerator implements Countable, IteratorAggregate
{
    /** @use Enumerable<TKey, TValue> */
    use Enumerable {
        count as protected traitCount;
    }

    /**
     * @var iterable<TKey, TValue> $items
     */
    protected iterable $items;

    /**
     * @param iterable<TKey, TValue> $items
     * Iterable elements to be used in collection.
     */
    public function __construct(
        iterable $items = [],
    )
    {
        if (!$items instanceof LazyIterator) {
            $items = Arr::from($items);
        }

        $this->items = $items;
    }

    /**
     * @inheritDoc
     * @return Traversable<TKey, TValue>
     */
    public function getIterator(): Traversable
    {
        yield from $this->items;
    }

    /**
     * NOTE: Overridden to prevent calling Arr::count() directly since it calls count()
     * internally if the given `$iterable` implements Countable, which will call itself
     * again and cause an infinite loop.
     *
     * @inheritDoc
     */
    #[Override]
    public function count(?Closure $condition = null): int
    {
        return $condition === null && is_countable($this->items)
            ? count($this->items)
            : $this->traitCount($condition);
    }

    /**
     * Returns a new instance which collection is iterated lazily for
     * some functions like `chunk`, `each`, `map`, and `filter`.
     *
     * @return static
     */
    public function lazy(): static
    {
        return $this->instantiate(new LazyIterator($this->items));
    }

    /**
     * Returns a new instance as an eager collection.
     *
     * @return static
     */
    public function eager(): static
    {
        return $this->instantiate(Arr::from($this));
    }

    /**
     * Returns **true** if collection is lazy. **false** otherwise.
     *
     * @return bool
     */
    public function isLazy(): bool
    {
        return $this->items instanceof LazyIterator;
    }

    /**
     * Returns **false** if collection is lazy. **true** otherwise.
     *
     * @return bool
     */
    public function isEager(): bool
    {
        return !$this->isLazy();
    }

    /**
     * Invokes `$callback` with `$this` as argument and returns `$this`.
     *
     * @param Closure($this): mixed $callback
     * Callback to be invoked.
     * @return $this
     */
    public function tap(Closure $callback): static
    {
        $callback($this);
        return $this;
    }
}
