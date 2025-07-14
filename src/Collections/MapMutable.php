<?php declare(strict_types=1);

namespace Kirameki\Collections;

use Kirameki\Collections\Utils\Arr;
use Override;
use function assert;
use function is_array;

/**
 * @template TKey of array-key
 * @template TValue
 * @extends Map<TKey, TValue>
 */
class MapMutable extends Map
{
    /**
     * @use MutatesSelf<TKey, TValue>
     */
    use MutatesSelf;

    /**
     * @param iterable<TKey, TValue> $items
     */
    public function __construct(iterable $items = [])
    {
        parent::__construct(Arr::from($items));
    }

    /**
     * @inheritDoc
     */
    #[Override]
    public function instantiate(mixed $iterable): static
    {
        return new static($iterable);
    }

    /**
     * Returns an immutable copy of this map.
     *
     * @return Map<TKey, TValue>
     */
    public function immutable(): Map
    {
        return new Map($this->items);
    }

    /**
     * Set the given key value pair to the collection.
     *
     * @param TKey $key
     * The key to be set.
     * @param TValue $value
     * The value to be set.
     * @return $this
     */
    public function set(int|string $key, mixed $value): static
    {
        assert(is_array($this->items));
        Arr::set($this->items, $key, $value);
        return $this;
    }

    /**
     * Set the given key value pair to the collection only if the entry already exists.
     *
     * @param TKey $key
     * The key to be set.
     * @param TValue $value
     * The value to be set.
     * @param bool &$result
     * [Optional] A bool reference to store the result of the operation.
     * @return $this
     */
    public function setIfExists(int|string $key, mixed $value, bool &$result = false): static
    {
        assert(is_array($this->items));
        $result = Arr::setIfExists($this->items, $key, $value);
        return $this;
    }

    /**
     * Set the given key value pair to the collection only if the entry does exist.
     *
     * @param TKey $key
     * The key to be set.
     * @param TValue $value
     * The value to be set.
     * @param bool &$result
     * [Optional] A bool reference to store the result of the operation.
     * @return $this
     */
    public function setIfNotExists(int|string $key, mixed $value, bool &$result = false): static
    {
        assert(is_array($this->items));
        $result = Arr::setIfNotExists($this->items, $key, $value);
        return $this;
    }
}
