<?php declare(strict_types=1);

namespace Kirameki\Collections;

use Kirameki\Collections\Utils\Arr;
use Kirameki\Core\Exceptions\InvalidArgumentException;
use function gettype;
use function is_int;

/**
 * @template TKey of array-key
 * @template TValue
 */
trait MutatesSelf
{
    /**
     * @param iterable<TKey, TValue> $iterable
     * @return static
     */
    abstract public function instantiate(iterable $iterable): static;

    /**
     * @return bool
     */
    abstract protected function reindex(): bool;

    /**
     * @return array<TKey, TValue>
     */
    abstract protected function &getItemsAsRef(): array;

    /**
     * @inheritDoc
     * @param int|string|null $offset
     * @param TValue $value
     * @return void
     */
    public function offsetSet(mixed $offset, mixed $value): void
    {
        $ref = &$this->getItemsAsRef();

        if ($offset === null && $this->reindex()) {
            $ref[] = $value;
            return;
        }

        if (!is_int($offset) && !is_string($offset)) {
            throw new InvalidArgumentException('Expected: $offset\'s type to be int|string. Got: ' . gettype($offset) . '.', [
                'this' => $this,
                'offset' => $offset,
                'value' => $value,
            ]);
        }

        $ref[$offset] = $value;
    }

    /**
     * @inheritDoc
     * @param mixed $offset
     * @return void
     */
    public function offsetUnset(mixed $offset): void
    {
        $ref = &$this->getItemsAsRef();
        Arr::pullOrNull($ref, $offset, $this->reindex());
    }

    /**
     * Clears all items in the collection.
     *
     * @return $this
     */
    public function clear(): static
    {
        $ref = &$this->getItemsAsRef();
        $ref = [];
        return $this;
    }

    /**
     * Inserts `$values` at the given `$index`.
     *
     * Throws `DuplicateKeyException` when the keys in `$values` already exist in the collection.
     * Change the `overwrite` argument to **true** to suppress this error.
     *
     * @param int $index
     * The position where the values will be inserted.
     * @param iterable<TKey, TValue> $values
     * One or more values that will be inserted.
     * @param bool $overwrite
     * [Optional] If **true**, duplicates will be overwritten for string keys.
     * If **false**, exception will be thrown on duplicate key.
     * Defaults to **false**.
     * @return $this
     */
    public function insertAt(int $index, iterable $values, bool $overwrite = false): static
    {
        $ref = &$this->getItemsAsRef();
        Arr::insertAt($ref, $index, $values, $this->reindex(), $overwrite);
        return $this;
    }

    /**
     * Pops the element off the end of the collection.
     * Throws `EmptyNotAllowedException`, if the collection is empty.
     *
     * @return TValue
     */
    public function pop(): mixed
    {
        $ref = &$this->getItemsAsRef();
        return Arr::pop($ref);
    }

    /**
     * Pops the element off the end of the collection.
     * Returns **null**, if the collection is empty.
     *
     * @return TValue|null
     */
    public function popOrNull(): mixed
    {
        $ref = &$this->getItemsAsRef();
        return Arr::popOrNull($ref);
    }

    /**
     * Pops elements off the end of the collection.
     * Returns the popped elements in a new instance.
     *
     * @param int $amount
     * Amount of elements to pop. Must be a positive integer.
     * @return static
     */
    public function popMany(int $amount): static
    {
        $ref = &$this->getItemsAsRef();
        return $this->instantiate(Arr::popMany($ref, $amount));
    }

    /**
     * Removes `$key` from the collection and returns the pulled value.
     * Throws `InvalidKeyException` if `$key` is not found.
     *
     * @param TKey $key
     * Key to be pulled from the collection.
     * @return TValue
     */
    public function pull(int|string $key): mixed
    {
        $ref = &$this->getItemsAsRef();
        return Arr::pull($ref, $key, $this->reindex());
    }

    /**
     * Removes `$key` from the collection and returns the pulled value.
     * If `$key` is not found, value of `$default` is returned instead.
     *
     * @template TDefault
     * @param TKey $key
     * Key to be pulled from the collection.
     * @param TDefault $default
     * Default value to be returned if `$key` is not found.
     * @return TValue|TDefault
     */
    public function pullOr(int|string $key, mixed $default): mixed
    {
        $ref = &$this->getItemsAsRef();
        return Arr::pullOr($ref, $key, $default, $this->reindex());
    }

    /**
     * Removes `$key` from the collection and returns the pulled value.
     * If `$key` is not found, **null** is returned instead.
     *
     * @param TKey $key
     * Key to be pulled from the collection.
     * @return TValue|null
     */
    public function pullOrNull(int|string $key): mixed
    {
        $ref = &$this->getItemsAsRef();
        return Arr::pullOrNull($ref, $key, $this->reindex());
    }

    /**
     * Removes `$keys` from the collection and returns the pulled values as list.
     * If `$key` does not exist, the missing key will be added to `$missed`.
     *
     * @param iterable<TKey> $keys
     * Keys or indexes to be pulled.
     * @param list<TKey>|null $missed
     * @param-out list<TKey>|null $missed
     * [Optional][Reference] `$keys` that did not exist.
     * @return static
     */
    public function pullMany(iterable $keys, ?array &$missed = null): static
    {
        $ref = &$this->getItemsAsRef();
        return $this->instantiate(Arr::pullMany($ref, $keys, $this->reindex(), $missed));
    }

    /**
     * Removes `$value` from the collection.
     * Limit can be set to specify the number of times a value should be removed.
     * Returns the keys of the removed value.
     *
     * @param TValue $value
     * Value to be removed.
     * @param int|null $limit
     * [Optional] Limits the number of items to be removed.
     * @return Vec<TKey>
     */
    public function remove(mixed $value, ?int $limit = null): Vec
    {
        $ref = &$this->getItemsAsRef();
        return $this->newVec(Arr::remove($ref, $value, $limit, $this->reindex()));
    }

    /**
     * Shift an element off the beginning of the collection.
     * Returns **null** if the collection is empty.
     *
     * @return TValue
     */
    public function shift(): mixed
    {
        $ref = &$this->getItemsAsRef();
        return Arr::shift($ref);
    }

    /**
     * Shift an element off the beginning of the collection.
     * Returns **null** if the collection is empty.
     *
     * @return TValue|null
     */
    public function shiftOrNull(): mixed
    {
        $ref = &$this->getItemsAsRef();
        return Arr::shiftOrNull($ref);
    }

    /**
     * Shift an element off the beginning of the collection up to `$amount`.
     * Returns the shifted elements as an array.
     *
     * @param int $amount
     * Amount of elements to be shifted.
     * Must be an integer with value >= 1.
     * @return static
     */
    public function shiftMany(int $amount): static
    {
        $ref = &$this->getItemsAsRef();
        return $this->instantiate(Arr::shiftMany($ref, $amount));
    }
}
