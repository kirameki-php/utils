<?php declare(strict_types=1);

namespace Kirameki\Collections;

use IteratorAggregate;
use Override;
use Traversable;

/**
 * @template-covariant TKey of array-key
 * @template-covariant TValue
 * @implements IteratorAggregate<TKey, TValue>
 */
final readonly class LazyIterator implements IteratorAggregate
{
    /**
     * @param iterable<TKey, TValue> $items
     * Iterable elements to be used in collection.
     */
    public function __construct(
        protected iterable $items,
    )
    {
    }

    /**
     * @inheritDoc
     * @return Traversable<TKey, TValue>
     */
    #[Override]
    public function getIterator(): Traversable
    {
        yield from $this->items;
    }
}
