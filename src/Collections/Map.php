<?php declare(strict_types=1);

namespace Kirameki\Collections;

use ArrayAccess;
use Closure;
use JsonSerializable;
use Kirameki\Collections\Utils\Arr;
use Kirameki\Collections\Utils\Iter;
use Kirameki\Core\Exceptions\NotSupportedException;
use Override;
use Random\Randomizer;
use function is_array;
use const SORT_REGULAR;

/**
 * @template TKey of array-key
 * @template TValue
 * @extends Enumerator<TKey, TValue>
 * @implements ArrayAccess<TKey, TValue>
 */
class Map extends Enumerator implements ArrayAccess, JsonSerializable
{
    /**
     * @inheritDoc
     */
    #[Override]
    public function instantiate(mixed $iterable): static
    {
        return new static($iterable);
    }

    /**
     * @return array<TKey, TValue>
     */
    protected function &getItemsAsRef(): array
    {
        $items = &$this->items;

        if (is_array($items)) {
            return $items;
        }

        $innerType = get_debug_type($items);
        throw new NotSupportedException("Map's inner item must be of type array|ArrayAccess, {$innerType} given.", [
            'this' => $this,
            'items' => $items,
        ]);
    }

    /**
     * @inheritDoc
     * @param TKey $offset
     * @return bool
     */
    public function offsetExists(mixed $offset): bool
    {
        $ref = $this->getItemsAsRef();
        return isset($ref[$offset]);
    }

    /**
     * @inheritDoc
     * @param TKey $offset
     * @return TValue
     */
    public function offsetGet(mixed $offset): mixed
    {
        $ref = $this->getItemsAsRef();
        return $ref[$offset];
    }

    /**
     * @private
     * @param TKey $offset
     * @param TValue $value
     * @return void
     */
    public function offsetSet(mixed $offset, mixed $value): void
    {
        throw new NotSupportedException(__METHOD__ . ' is not supported.');
    }

    /**
     * @private
     * @param TKey $offset
     * @return void
     */
    public function offsetUnset(mixed $offset): void
    {
        throw new NotSupportedException(__METHOD__ . ' is not supported.');
    }

    /**
     * @inheritDoc
     * @return object
     */
    public function jsonSerialize(): object
    {
        return (object) Arr::from($this);
    }

    /**
     *  Returns **true** if `$iterable` contains all the provided `$keys`,
     *  **false** otherwise.
     *
     * @param iterable<int, TKey> $keys
     *  Keys to be searched.
     * @return bool
     */
    public function containsAllKeys(iterable $keys): bool
    {
        return Arr::containsAllKeys($this, $keys);
    }

    /**
     *  Returns **true** if `$iterable` contains any of the provided `$keys`,
     *  **false** otherwise.
     *
     * @param iterable<int, TKey> $keys
     * Keys to be searched.
     * @return bool
     */
    public function containsAnyKeys(iterable $keys): bool
    {
        return Arr::containsAnyKeys($this, $keys);
    }

    /**
     *  Returns **true** if a given key exists within iterable, **false** otherwise.
     *
     * @param TKey $key
     * Key to be searched.
     * @return bool
     */
    public function containsKey(mixed $key): bool
    {
        return Arr::containsKey($this, $key);
    }

    /**
     * Compares the keys from the collection against the keys from `$items` and returns the difference.
     *
     * @param iterable<TKey, TValue> $items
     * Items to be compared with the collection.
     * @param Closure(TKey, TKey): int|null $by
     * [Optional] Callback which can be used for comparison of items in both iterables.
     * @return static
     */
    public function diffKeys(iterable $items, ?Closure $by = null): static
    {
        return $this->instantiate(Arr::diffKeys($this, $items, $by, $this->reindex()));
    }

    /**
     * Returns **false** if a given key exists within iterable, **true** otherwise.
     *
     * @param TKey $key
     * Key to be searched.
     * @return bool
     */
    public function doesNotContainKey(mixed $key): bool
    {
        return Arr::doesNotContainKey($this, $key);
    }

    /**
     * Returns a new instance with the given keys removed. Missing keys will be ignored.
     * If `$safe` is set to **true**, `MissingKeyException` will be thrown if a key does not exist.
     *
     * @param iterable<int, TKey> $keys
     * Keys to be excluded.
     * @param bool $safe
     * [Optional] If this is set to **true**, `MissingKeyException` will be
     * thrown if key does not exist in the collection.
     * If set to **false**, non-existing keys will be filled with **null**.
     * Defaults to **true**.
     * @return static
     */
    public function dropKeys(iterable $keys, bool $safe = true): static
    {
        return $this->instantiate(Arr::dropKeys($this, $keys, $safe, $this->reindex()));
    }

    /**
     * Ensures that collection only contains the given `$keys`.
     * Throws `ExcessKeyException` if `$iterable` contains more keys than `$keys`.
     * Throws `MissingKeyException` if `$iterable` contains fewer keys than `$keys`.
     *
     * @param iterable<int, TKey> $keys
     * Keys to be checked.
     * @return $this
     */
    public function ensureExactKeys(iterable $keys): static
    {
        Arr::ensureExactKeys($this, $keys);
        return $this;
    }

    /**
     * Returns the first key of the collection which meets the given `$condition`.
     * Throws `NoMatchFoundException` if no condition is met.
     * Throws `EmptyNotAllowedException` if the collection is empty.
     *
     * @param Closure(TValue, TKey): bool|null $condition
     * [Optional] User defined condition callback. The callback must return a boolean value.
     * Defaults to **null**.
     * @return TKey
     */
    public function firstKey(?Closure $condition = null): mixed
    {
        return Arr::firstKey($this, $condition);
    }

    /**
     * Returns the first key of the collection which meets the given `$condition`.
     * Returns **null** if the collection is empty or if there were no matching conditions.
     *
     * @param Closure(TValue, TKey): bool|null $condition
     * [Optional] User defined condition callback. The callback must return a boolean value.
     * Defaults to **null**.
     * @return TKey|null
     */
    public function firstKeyOrNull(?Closure $condition = null): mixed
    {
        return Arr::firstKeyOrNull($this, $condition);
    }

    /**
     * Returns the element of `$key`.
     * Throws `InvalidKeyException` if key does not exist.
     *
     * @param TKey $key
     * Key to look for.
     * @return TValue
     */
    public function get(int|string $key): mixed
    {
        return Arr::get($this, $key);
    }

    /**
     * Returns the element of `$key` if it exists, `$default` is returned otherwise.
     *
     * @template TDefault
     * @param TKey $key
     * Key to look for.
     * @param TDefault $default
     * Default value to return if key is not found.
     * @return TValue|TDefault
     */
    public function getOr(int|string $key, mixed $default): mixed
    {
        return Arr::getOr($this, $key, $default);
    }

    /**
     * Returns the element of `$key` if it exists, `null` otherwise.
     *
     * @param TKey $index
     * Index to look for.
     * @return TValue|null
     */
    public function getOrNull(int|string $index): mixed
    {
        return Arr::getOrNull($this, $index);
    }

    /**
     * Returns the key at `$index`.
     * Throws `IndexOutOfBoundsException` if the index does not exist.
     *
     * @param int $index
     * Index to look for.
     * @return TKey
     */
    public function keyAt(int $index): int|string
    {
        /** @var TKey */
        return Arr::keyAt($this, $index);
    }

    /**
     * Returns the key at `$index`.
     * Returns **null** if the index does not exist.
     *
     * @param int $index
     * Index to look for.
     * @return TKey|null
     */
    public function keyAtOrNull(int $index): string|int|null
    {
        return Arr::keyAtOrNull($this, $index);
    }

    /**
     * Returns all the keys as `Vec`.
     *
     * @return Vec<TKey>
     */
    public function keys(): Vec
    {
        return $this->newVec(Iter::keys($this));
    }

    /**
     * Returns the intersection of the collection using keys for comparison.
     *
     * @param iterable<TKey, TValue> $items
     * Items to be intersected.
     * @return static
     */
    public function intersectKeys(iterable $items): static
    {
        return $this->instantiate(Arr::intersectKeys($this, $items));
    }

    /**
     * Returns the last key of the collection which meets the `$condition`.
     * Throws `NoMatchFoundException` if no condition is met.
     * Throws `EmptyNotAllowedException` if the collection is empty.
     *
     * @param Closure(TValue, TKey): bool|null $condition
     * [Optional] User defined condition callback. The callback must return a boolean value.
     * Defaults to **null**.
     * @return TKey
     */
    public function lastKey(?Closure $condition = null): mixed
    {
        return Arr::lastKey($this, $condition);
    }

    /**
     * Returns the last key of the collection which meets the `$condition`.
     * Returns **null** if condition is not met.
     *
     * @param Closure(TValue, TKey): bool|null $condition
     * [Optional] User defined condition callback. The callback must return a boolean value.
     * Defaults to **null**.
     * @return TKey|null
     */
    public function lastKeyOrNull(?Closure $condition = null): mixed
    {
        return Arr::lastKeyOrNull($this, $condition);
    }

    /**
     * Returns a new instance containing results returned from invoking
     * `$callback` on each element of the collection.
     *
     * @template TMapValue
     * @param Closure(TValue, TKey): TMapValue $callback
     * Callback to be used to map the values.
     * @return self<TKey, TMapValue>
     */
    public function map(Closure $callback): self
    {
        return $this->newMap(Arr::map($this, $callback));
    }

    /**
     * Converts collection to a mutable instance.
     *
     * @return MapMutable<TKey, TValue>
     */
    public function mutable(): MapMutable
    {
        return new MapMutable($this->items);
    }

    /**
     * Returns a random key picked from the collection.
     * Throws `EmptyNotAllowedException` if the collection is empty.
     *
     * @param Randomizer|null $randomizer
     * [Optional] Randomizer to be used.
     * Default randomizer (Secure) will be used if **null**.
     * Defaults to **null**.
     * @return TKey
     */
    public function sampleKey(?Randomizer $randomizer = null): mixed
    {
        return Arr::sampleKey($this, $randomizer);
    }

    /**
     * Returns a random key picked from the collection.
     * Returns **null** if the collection is empty.
     *
     * @param Randomizer|null $randomizer
     * [Optional] Randomizer to be used.
     * Default randomizer (Secure) will be used if **null**.
     * Defaults to **null**.
     * @return TKey|null
     */
    public function sampleKeyOrNull(?Randomizer $randomizer = null): mixed
    {
        /** @var TKey|null needed for some reason by phpstan */
        return Arr::sampleKeyOrNull($this, $randomizer);
    }

    /**
     * Returns a list of random elements picked as `Vec`.
     * If `$replace` is set to **false**, each key will be chosen only once.
     * Throws `InvalidArgumentException` if `$amount` is larger than `$iterable`'s size.
     *
     * @param int $amount
     * Amount of items to sample.
     * @param bool $replace
     * If **true**, same elements can be chosen more than once.
     * Defaults to **false**.
     * @param Randomizer|null $randomizer
     * [Optional] Randomizer to be used.
     * Default randomizer (Secure) will be used if **null**.
     * Defaults to **null**.
     * @return Vec<TKey>
     */
    public function sampleKeys(int $amount, bool $replace = false, ?Randomizer $randomizer = null): Vec
    {
        return $this->newVec(Arr::sampleKeys($this, $amount, $replace, $randomizer));
    }

    /**
     * Sort the collection by key in ascending order.
     *
     * @param bool $ascending
     * Sort in ascending order if **true**, descending order if **false**.
     * @param int $flag
     * [Optional] Sort flag to change the behavior of the sort.
     * See https://www.php.net/manual/en/function.sort.php for more info.
     * Defaults to `SORT_REGULAR`.
     * @return static
     */
    public function sortByKey(bool $ascending, int $flag = SORT_REGULAR): static
    {
        return $this->instantiate(Arr::sortByKey($this, $ascending, $flag));
    }

    /**
     * Sort the `$iterable` by key in ascending order.
     *
     * @param int $flag
     * [Optional] Sort flag to change the behavior of the sort.
     * See https://www.php.net/manual/en/function.sort.php for more info.
     * Defaults to `SORT_REGULAR`.
     * @return static
     */
    public function sortByKeyAsc(int $flag = SORT_REGULAR): static
    {
        return $this->instantiate(Arr::sortByKeyAsc($this, $flag));
    }

    /**
     * Sort the `$iterable` by key in descending order.
     *
     * @param int $flag
     * [Optional] Sort flag to change the behavior of the sort.
     * See https://www.php.net/manual/en/function.sort.php for more info.
     * Defaults to `SORT_REGULAR`.
     * @return static
     */
    public function sortByKeyDesc(int $flag = SORT_REGULAR): static
    {
        return $this->instantiate(Arr::sortByKeyDesc($this, $flag));
    }

    /**
     * Sorts the `$iterable` by key using the provided comparison function.
     *
     * @param Closure(TKey, TKey): int $comparator
     * The comparison function to use.
     * Utilize the spaceship operator (`<=>`) to easily compare two values.
     * @return static
     */
    public function sortWithKey(Closure $comparator): static
    {
        return $this->instantiate(Arr::sortWithKey($this, $comparator));
    }

    /**
     * Returns a new collection with the elements of the two keys swapped.
     *
     * @param TKey $key1
     * Key to be swapped.
     * @param TKey $key2
     * Key to be swapped.
     * @return static
     */
    public function swap(int|string $key1, int|string $key2): static
    {
        return $this->instantiate(Arr::swap($this, $key1, $key2, $this->reindex()));
    }

    /**
     * Returns a new collection which only contains the elements that has matching
     * keys in the collection. Non-existent keys will be ignored.
     * If `$safe` is set to **true**, `MissingKeyException` will be thrown
     * if a key does not exist in the collection.
     *
     * @param iterable<int, TKey> $keys
     * Keys to be included.
     * @param bool $safe
     * [Optional] If this is set to **true**, `MissingKeyException` will be
     * thrown if key does not exist in the collection.
     * If set to **false**, non-existing keys will be filled with **null**.
     * Defaults to **true**.
     * @return static
     */
    public function takeKeys(iterable $keys, bool $safe = true): static
    {
        return $this->instantiate(Arr::takeKeys($this, $keys, $safe, $this->reindex()));
    }

    /**
     * Generates URL encoded query string from the elements of the collection.
     * Encoding follows RFC3986 (spaces will be converted to `%20`).
     *
     * @param string|null $namespace
     * [Optional] Adds namespace to wrap the iterable.
     * Defaults to **null**.
     * @return string
     */
    public function toUrlQuery(?string $namespace = null): string
    {
        return Arr::toUrlQuery($this, $namespace);
    }

    /**
     * Returns a copy of collection with the specified `$defaults` merged in if
     * the corresponding key does not exist `$iterable`.
     *
     * @param iterable<TKey, TValue> $defaults
     * Iterable to be set as default.
     * @return static
     */
    public function withDefaults(iterable $defaults): static
    {
        return $this->instantiate(Arr::withDefaults($this, $defaults));
    }

    /**
     * Returns a new `Vec` which contains the values of the collection.
     *
     * @return Vec<TValue>
     */
    public function values(): Vec
    {
        return $this->newVec(Iter::values($this));
    }

    /**
     * @inheritDoc
     */
    #[Override]
    protected function reindex(): bool
    {
        return false;
    }
}
