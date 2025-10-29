<?php declare(strict_types=1);

namespace Kirameki\Collections;

use Closure;
use Generator;
use Kirameki\Collections\Utils\Arr;
use Kirameki\Collections\Utils\Iter;
use Kirameki\Core\Exceptions\InvalidArgumentException;
use Kirameki\Core\Exceptions\TypeMismatchException;
use Kirameki\Core\Json;
use Random\Randomizer;
use function array_map;
use function gettype;
use function is_bool;
use function is_iterable;
use const PHP_INT_MAX;
use const SORT_REGULAR;

/**
 * @template TKey of array-key
 * @template TValue
 */
trait Enumerable
{
    /**
     * On certain operations, there are options to reindex the array or not.
     * This is a helper method to determine whether the array should be re-indexed.
     * For example, when you remove an item from the array, the array should be
     * re-indexed if it is a vector (Vec), but not when it is a hash-table (Map).
     *
     * @return bool
     */
    abstract protected function reindex(): bool;

    /**
     * Returns the inner items as array.
     *
     * @return array<TKey, TValue>
     */
    public function all(): array
    {
        return Arr::from($this);
    }

    /**
     * Returns the value at the given index.
     * Throws `IndexOutOfBoundsException` if the index does not exist.
     *
     * @param int $index
     * Index of iterable starting with 0. Negative index will traverse from tail.
     * @return TValue
     */
    public function at(int $index)
    {
        return Arr::at($this, $index);
    }

    /**
     * Returns the value at the given index.
     * Returns `$default` if the given index does not exist.
     *
     * @template TDefault
     * @param int $index
     * Index of iterable starting with 0. Negative index will traverse from tail.
     * @param TDefault $default
     * Value that is used when the given index did not exist.
     * @return TValue|TDefault
     */
    public function atOr(int $index, mixed $default)
    {
        return Arr::atOr($this, $index, $default);
    }

    /**
     * Returns the value at the given index.
     * Returns **null** if the given index does not exist.
     *
     * @param int $index
     * Index of iterable starting with 0. Negative index will traverse from tail.
     * @return TValue|null
     */
    public function atOrNull(int $index)
    {
        return Arr::atOrNull($this, $index);
    }

    /**
     * Splits the collection into chunks of the given size.
     *
     * @param int<1, max> $size
     * Size of each chunk. Must be >= 1.
     * @return Vec<static>
     */
    public function chunk(int $size): Vec
    {
        $chunks = [];
        foreach (Iter::chunk($this, $size, $this->reindex()) as $chunk) {
            $converted = $this->instantiate($chunk);
            $chunks[] = $converted;
        }
        return $this->newVec($chunks);
    }

    /**
     * Returns the first non-null value.
     * Throws `InvalidArgumentException` if empty or all elements are **null**.
     *
     * @return TValue
     */
    public function coalesce(): mixed
    {
        return Arr::coalesce($this);
    }

    /**
     * Returns the first non-null value.
     * Returns **null** if collection is empty or if all elements are **null**.
     *
     * @return TValue|null
     */
    public function coalesceOrNull(): mixed
    {
        return Arr::coalesceOrNull($this);
    }

    /**
     * Returns a new `Vec` that contains the results sent from the Generator returned by the `$callback`.
     *
     * @template TMapValue
     * @param Closure(TValue, TKey): Generator<int, TMapValue> $callback
     * Closure that will be called for each key/value. The returned value will be yielded.
     * @return Vec<TMapValue>
     */
    public function collect(
        Closure $callback,
    ): Vec
    {
        return $this->newVec(Iter::collect($this, $callback));
    }

    /**
     * Returns **true** if value exists, **false** otherwise.
     *
     * @param TValue $value
     * Value to be searched.
     * @return bool
     */
    public function contains(mixed $value): bool
    {
        return Arr::contains($this, $value);
    }

    /**
     * Returns **true** if all given values exist in the collection,
     * **false** otherwise.
     *
     * @param iterable<int, TValue> $values
     * Values to be searched.
     * @return bool
     */
    public function containsAll(iterable $values): bool
    {
        return Arr::containsAll($this, $values);
    }

    /**
     * Returns **true** if any given values exist in the collection,
     * **false** otherwise.
     *
     * @param iterable<int, TValue> $values
     * Values to be searched.
     * @return bool
     */
    public function containsAny(iterable $values): bool
    {
        return Arr::containsAny($this, $values);
    }

    /**
     * Returns **true** if none of the given values exist in the
     * collection, **false** otherwise.
     *
     * @param iterable<int, TValue> $values
     * Values to be searched.
     * @return bool
     */
    public function containsNone(iterable $values): bool
    {
        return Arr::containsNone($this, $values);
    }

    /**
     * Returns **true** if collection contains the given slice of `$values`,
     * **false** otherwise.
     *
     * @param iterable<int, TValue> $values
     * Values to be searched.
     * @return bool
     */
    public function containsSlice(iterable $values): bool
    {
        return Arr::containsSlice($this, $values);
    }

    /**
     * Counts all the elements in the collection.
     * If a condition is given, it will only increase the count if the condition returns **true**.
     *
     * @param Closure(TValue, TKey): bool|null $condition
     * [Optional] Condition to determine if given item should be counted.
     * Defaults to **null**.
     * @return int<0, max>
     */
    public function count(?Closure $condition = null): int
    {
        return Arr::count($this->items, $condition);
    }

    /**
     * Counts and checks the collection to see if the amount is equal to `$size`.
     *
     * @param int $size
     * Size to be compared to.
     * @return bool
     */
    public function countIs(int $size): bool
    {
        return $this->count() === $size;
    }

    /**
     * Checks and returns **true** if count of collection is between `$start` and `$end`,
     * **false** otherwise. The range is inclusive meaning countIsBetween(1, 10) will include
     * the numbers 1 and 10. Throws `InvalidArgumentException` if `$end` is smaller than `$start`.
     *
     * @param int $start
     * Start of the range.
     * @param int $end
     * End of the range.
     * @return bool
     */
    public function countIsBetween(int $start, int $end): bool
    {
        if ($end < $start) {
            throw new InvalidArgumentException('`$end` must be >= `$start`.');
        }

        $count = $this->count();
        return $count >= $start && $count <= $end;
    }

    /**
     * Compares the keys against the values from `$items` and returns the difference.
     *
     * @param iterable<TKey, TValue> $items
     * Iterable to be compared with.
     * @param Closure(TValue, TValue): int<-1, 1>|null $by
     * [Optional] Callback which can be used for comparison of items.
     * @return static
     */
    public function diff(
        iterable $items,
        ?Closure $by = null,
    ): static
    {
        return $this->instantiate(Arr::diff($this, $items, $by, $this->reindex()));
    }

    /**
     * Returns **false** if value exists, **true** otherwise.
     *
     * @param TValue $value
     * Value to be searched.
     * @return bool
     */
    public function doesNotContain(mixed $value): bool
    {
        return Arr::doesNotContain($this, $value);
    }

    /**
     * Returns a new instance with every nth elements dropped.
     *
     * @param int $nth
     * Nth value to drop. Must be >= 1.
     * @return static
     */
    public function dropEvery(int $nth): static
    {
        return $this->instantiate(Arr::dropEvery($this, $nth, $this->reindex()));
    }

    /**
     * Returns a new instance with the first n elements dropped.
     *
     * @param int $amount
     * Amount of elements to drop from the front. Must be >= 0.
     * @return static
     */
    public function dropFirst(int $amount): static
    {
        return $this->instantiate(Iter::dropFirst($this, $amount, $this->reindex()));
    }

    /**
     * Returns a new collection that contains all elements where the `$condition` returned **false**.
     *
     * Iterable to be traversed.
     * @param Closure(TValue, TKey): bool $condition
     * A condition that should return a boolean.
     * @return static
     */
    public function dropIf(Closure $condition): static
    {
        return $this->instantiate(Iter::dropIf($this, $condition, $this->reindex()));
    }

    /**
     * Returns a new instance with the last n elements dropped.
     *
     * @param int $amount
     * Amount of items to be dropped from the end. Must be >= 0.
     * @return static
     */
    public function dropLast(int $amount): static
    {
        return $this->instantiate(Arr::dropLast($this, $amount));
    }

    /**
     * Returns a new instance with the values dropped until the condition returns **true**.
     *
     * @param Closure(TValue, TKey): bool $condition
     * A condition that should return a boolean value.
     * @return static
     */
    public function dropUntil(Closure $condition): static
    {
        return $this->instantiate(Iter::dropUntil($this, $condition, $this->reindex()));
    }

    /**
     * Returns a new instance with the values dropped while the condition returns **true**.
     *
     * @param Closure(TValue, TKey): bool $condition
     * A condition that should return a boolean value.
     * @return static
     */
    public function dropWhile(Closure $condition): static
    {
        return $this->instantiate(Iter::dropWhile($this, $condition, $this->reindex()));
    }

    /**
     * Returns duplicate values.
     *
     * @return static
     */
    public function duplicates(): static
    {
        return $this->instantiate(Arr::duplicates($this));
    }

    /**
     * Iterates through the collection and invoke `$callback` for each element.
     *
     * @param Closure(TValue, TKey): (mixed|void) $callback
     * Callback which is called for every element of the collection.
     * @return static
     */
    public function each(Closure $callback): static
    {
        return $this->instantiate(Iter::each($this, $callback));
    }

    /**
     * Ensures that count of this collection is equal to `$size`.
     * Throws `CountMismatchException` if count is not equal to `$size`.
     *
     * @param int $size
     * Expected size of the iterable.
     * @return $this
     */
    public function ensureCountIs(int $size): static
    {
        Arr::ensureCountIs($this, $size);
        return $this;
    }

    /**
     * Ensures that all elements of the collection are of the given `$type`.
     * Throws `InvalidTypeException` if `$type` is not a valid type.
     * Throws `TypeMismatchException` if any element is not of the expected type.
     * Empty collections are considered valid.
     *
     * @param string $type
     * Type(s) to be checked against. Ex: 'int|string|null'
     * @return $this
     */
    public function ensureElementType(string $type): static
    {
        Arr::ensureElementType($this, $type);
        return $this;
    }

    /**
     * Returns **true** if the collection ends with the given `$values`, **false** otherwise.
     *
     * @param iterable<int, TValue> $values
     * Values to be searched.
     * @return bool
     */
    public function endsWith(
        iterable $values,
    ): bool
    {
        return Arr::endsWith($this, $values);
    }

    /**
     * Returns a new collection that contains all elements where the `$condition` returned **true**.
     *
     * Alias of `static::takeIf()`
     *
     * Iterable to be traversed.
     * @param Closure(TValue, TKey): bool $condition
     * A condition that should return a boolean.
     * @return static
     */
    public function filter(Closure $condition): static
    {
        return $this->takeIf($condition);
    }

    /**
     * Returns the first element in the collection.
     * If `$condition` is set, the first element which meets the condition is returned instead.
     * Throws `NoMatchFoundException` if no condition is met.
     * Throws `EmptyNotAllowedException` if collection is empty.
     *
     * @param Closure(TValue, TKey): bool|null $condition
     * [Optional] User defined condition callback. The callback must return a boolean value.
     * Defaults to **null**.
     * @return TValue
     */
    public function first(?Closure $condition = null): mixed
    {
        return Arr::first($this, $condition);
    }

    /**
     * Returns the first index of the collection which meets the given `$condition`.
     * Throws `NoMatchFoundException` if no condition is met.
     *
     * @param Closure(TValue, TKey):bool $condition
     * User defined condition callback. The callback must return a boolean value.
     * @return int
     */
    public function firstIndex(Closure $condition): ?int
    {
        return Arr::firstIndex($this, $condition);
    }

    /**
     * Returns the first index of the collection which meets the given `$condition`.
     * Returns **null** if there were no matches.
     *
     * @param Closure(TValue, TKey):bool $condition
     * User defined condition callback. The callback must return a boolean value.
     * @return int|null
     */
    public function firstIndexOrNull(Closure $condition): ?int
    {
        return Arr::firstIndexOrNull($this, $condition);
    }

    /**
     * Returns the first element in the collection.
     * If `$condition` is set, the first element which meets the condition is returned instead.
     * If condition has no matches, value of `$default` is returned.
     *
     * @template TDefault
     * @param TDefault $default
     * Value that is used when the given `$condition` has no match.
     * @param Closure(TValue, TKey): bool|null $condition
     * [Optional] User defined condition callback. The callback must return a boolean value.
     * Defaults to **null**.
     * @return TValue|null
     */
    public function firstOr(mixed $default, ?Closure $condition = null): mixed
    {
        return Arr::firstOr($this, $default, $condition);
    }

    /**
     * Returns the first element in the collection.
     * If `$condition` is set, the first element which meets the condition is returned instead.
     * **null** is returned, if no element matches the `$condition` or is empty.
     *
     * @param Closure(TValue, TKey): bool|null $condition
     * [Optional] User defined condition callback. The callback must return a boolean value.
     * Defaults to **null**.
     * @return TValue|null
     */
    public function firstOrNull(?Closure $condition = null): mixed
    {
        return Arr::firstOrNull($this, $condition);
    }

    /**
     * Take all the values in the collection and fold it into a single value.
     *
     * @template U
     * @param U $initial
     * The initial value passed to the first Closure as result.
     * @param Closure(U, TValue, TKey): U $callback
     * Callback which is called for every key-value pair in the collection.
     * The callback arguments are `(mixed $result, mixed $value, mixed $key)`.
     * The returned value would be used as $result for the subsequent call.
     * @return U
     */
    public function fold(mixed $initial, Closure $callback): mixed
    {
        return Arr::fold($this, $initial, $callback);
    }

    /**
     * Groups the elements of the collection according to the string
     * returned by `$callback`.
     *
     * @template TGroupKey of array-key
     * @param Closure(TValue, TKey): TGroupKey $callback
     * Callback to determine the group of the element.
     * @return Map<TGroupKey, static>
     */
    public function groupBy(Closure $callback): Map
    {
        $grouped = Arr::groupBy($this, $callback, $this->reindex());
        return $this->newMap($grouped)->map(fn($group) => $this->instantiate($group));
    }

    /**
     * Create a new instance of the collection with the given `$items`.
     *
     * @param iterable<TKey, TValue> $iterable
     * Iterable elements to be used in collection
     * @return static
     */
    abstract public function instantiate(mixed $iterable): static;

    /**
     * Returns the intersection of collection's values.
     *
     * @param iterable<TKey, TValue> $items
     * Iterable to be intersected.
     * @return static
     */
    public function intersect(iterable $items): static
    {
        return $this->instantiate(Arr::intersect($this, $items, $this->reindex()));
    }

    /**
     * Returns **true** if empty, **false** otherwise.
     *
     * @return bool
     */
    public function isEmpty(): bool
    {
        return Arr::isEmpty($this);
    }

    /**
     * Returns **true** if not empty, **false** otherwise.
     *
     * @return bool
     */
    public function isNotEmpty(): bool
    {
        return Arr::isNotEmpty($this);
    }

    /**
     * Concatenates all the elements in the collection into a single
     * string using the provided `$glue`. Optional prefix and suffix can
     * also be added to the result string.
     *
     * @param string $glue
     * String used to join the elements.
     * @param string|null $prefix
     * [Optional] Prefix added to the joined string.
     * @param string|null $suffix
     * [Optional] Suffix added to the joined string.
     * @return string
     */
    public function join(string $glue, ?string $prefix = null, ?string $suffix = null): string
    {
        return Arr::join($this, $glue, $prefix, $suffix);
    }

    /**
     * Returns a map which contains values from the collection with the keys
     * being the results of running `$callback($val, $key)` on each element.
     *
     * Throws `DuplicateKeyException` when the value returned by `$callback`
     * already exist in `$array` as a key. Set `$overwrite` to **true** to
     * suppress this error.
     *
     * @template TNewKey of array-key
     * @param Closure(TValue, TKey): TNewKey $callback
     * Callback which returns the key for the new map.
     * @param bool $overwrite
     * [Optional] If **true**, duplicate keys will be overwritten. Defaults to **false**.
     * If **false**, exception will be thrown on duplicate keys.
     * @return Map<TNewKey, TValue>
     */
    public function keyBy(
        Closure $callback,
        bool $overwrite = false,
    ): Map
    {
        return $this->newMap(Arr::keyBy($this, $callback, $overwrite));
    }

    /**
     * Returns the last element in the collection.
     * If `$condition` is set, the last element which meets the condition is returned instead.
     * Throws `NoMatchFoundException` if no condition is met.
     * Throws `EmptyNotAllowedException` if collection is empty.
     *
     * @param Closure(TValue, TKey): bool|null $condition
     * [Optional] User defined condition callback. The callback must return a boolean value.
     * Defaults to **null**.
     * @return TValue
     */
    public function last(?Closure $condition = null): mixed
    {
        return Arr::last($this, $condition);
    }

    /**
     * Returns the last index which meets the given `$condition`.
     * Throws `NoMatchFoundException` if no condition is met.
     * Throws `EmptyNotAllowedException` if collection is empty.
     *
     * @param Closure(TValue, TKey): bool|null $condition
     * [Optional] User defined condition callback. The callback must return a boolean value.
     * Defaults to **null**.
     * @return int
     */
    public function lastIndex(?Closure $condition = null): int
    {
        return Arr::lastIndex($this, $condition);
    }

    /**
     * Returns the last index which meets the given `$condition`.
     * Returns **null** if there were no matches.
     *
     * @param Closure(TValue, TKey): bool|null $condition
     * [Optional] User defined condition callback. The callback must return a boolean value.
     * Defaults to **null**.
     * @return int|null
     */
    public function lastIndexOrNull(?Closure $condition = null): ?int
    {
        return Arr::lastIndexOrNull($this, $condition);
    }

    /**
     * Returns the last element in the collection.
     * If `$condition` is set, the last element which meets the condition is returned instead.
     * Returns the value of `$default` if no condition met.
     *
     * @template TDefault
     * @param TDefault $default
     * Value that is used when the given `$condition` has no match.
     * @param Closure(TValue, TKey): bool|null $condition
     * [Optional] User defined condition callback. The callback must return a boolean value.
     * Defaults to **null**.
     * @return TValue|TDefault
     */
    public function lastOr(
        mixed $default,
        ?Closure $condition = null,
    ): mixed
    {
        return Arr::lastOr($this, $default, $condition);
    }

    /**
     * Returns the last element in the collection.
     * If `$condition` is set, the last element which meets the condition is returned instead.
     * Returns **null** if no element matches the `$condition` or is empty.
     *
     * @param Closure(TValue, TKey): bool|null $condition
     * [Optional] User defined condition callback. The callback must return a boolean value.
     * Defaults to **null**.
     * @return TValue|null
     */
    public function lastOrNull(?Closure $condition = null): mixed
    {
        return Arr::lastOrNull($this, $condition);
    }

    /**
     * Returns a new Map with key value pair returned for each iterable returned by calling `$callback`.
     *
     * @template TMapKey as array-key
     * @template TMapValue
     * @param Closure(TValue, int): iterable<TMapKey, TMapValue> $callback
     * Callback to be used to map the values.
     * @param bool $overwrite
     * [Optional] If **true**, duplicate keys will be overwritten. Defaults to **false**.
     * If **false**, exception will be thrown on duplicate keys.
     * @return Map<TMapKey, TMapValue>
     */
    public function mapWithKey(
        Closure $callback,
        bool $overwrite = false,
    ): self
    {
        return $this->newMap(Arr::mapWithKey($this, $callback, $overwrite));
    }

    /**
     * Returns the largest element in the collection.
     * If `$by` is given, each element will be passed to the closure and the
     * largest value returned from the closure will be returned instead.
     * Throws `InvalidElementException`, If collection contains NAN.
     * Throws `EmptyNotAllowedException` if collection is empty.
     *
     * @param Closure(TValue, TKey): mixed|null $by
     * [Optional] Called for every element in the collection.
     * Returned value will be used to determine the largest number.
     * @return TValue
     */
    public function max(?Closure $by = null): mixed
    {
        return Arr::max($this, $by);
    }

    /**
     * Returns the largest element in the collection.
     * If `$by` is given, each element will be passed to the closure and the
     * largest value returned from the closure will be returned instead.
     * Returns **null** if the collection is empty.
     * Throws `InvalidElementException` if collection contains NAN.
     *
     * @param Closure(TValue, TKey): mixed|null $by
     * [Optional] Called for every element in the collection.
     * Returned value will be used to determine the largest number.
     * Must be int or float.
     * @return TValue|null
     */
    public function maxOrNull(?Closure $by = null): mixed
    {
        return Arr::maxOrNull($this, $by);
    }

    /**
     * Merges one or more iterables into a single collection.
     *
     * If the given keys are numeric, the keys will be re-numbered with
     * an incremented number from the last number in the new collection.
     *
     * If the two iterables have the same keys, the value inside the
     * iterable that comes later will overwrite the value in the key.
     *
     * This method will only merge the key value pairs of the root depth.
     *
     * @param iterable<TKey, TValue> $iterables
     * Iterable(s) to be merged.
     * @return static
     */
    public function merge(iterable ...$iterables): static
    {
        return $this->instantiate(Arr::merge($this, ...$iterables));
    }

    /**
     * Merges one or more iterables recursively into a single collection.
     * Will merge recursively up to the given depth.
     *
     * @see merge for details on how keys and values are merged.
     *
     * @param iterable<TKey, TValue> $iterable
     * Iterable to be merged.
     * @param int<1, max> $depth
     * [Optional] Depth of recursion. Defaults to **PHP_INT_MAX**.
     * @return static
     */
    public function mergeRecursive(
        iterable $iterable,
        int $depth = PHP_INT_MAX,
    ): static
    {
        return $this->instantiate(Arr::mergeRecursive($this, $iterable, $depth));
    }

    /**
     * Returns the smallest element in the collection.
     * If `$by` is given, each element will be passed to the closure and the
     * smallest value returned from the closure will be returned instead.
     * Throws `EmptyNotAllowedException` if collection is empty.
     * Throws `InvalidElementException` if collection contains NAN.
     *
     * @param Closure(TValue, TKey): mixed|null $by
     * [Optional] Called for every element in the collection.
     * Returned value will be used to determine the smallest number.
     * Must be int or float.
     * @return TValue
     */
    public function min(?Closure $by = null): mixed
    {
        return Arr::min($this, $by);
    }

    /**
     * Returns the smallest element in the collection.
     * If `$by` is given, each element will be passed to the closure and the
     * smallest value returned from the closure will be returned instead.
     * Returns **null** if the collection is empty.
     * Throws `InvalidElementException` if collection contains NAN.
     *
     * @param Closure(TValue, TKey): mixed|null $by
     * [Optional] Called for every element in the collection.
     * Returned value will be used to determine the smallest number.
     * Must be int or float.
     * @return TValue|null
     */
    public function minOrNull(?Closure $by = null): mixed
    {
        return Arr::minOrNull($this, $by);
    }

    /**
     * Returns the smallest and largest element from the collection as array{ min: , max: }.
     * If `$by` is given, each element will be passed to the closure and the
     * smallest and largest value returned from the closure will be returned instead.
     * Throws `EmptyNotAllowedException` if collection is empty.
     * Throws `InvalidElementException` if collection contains NAN.
     *
     * @param Closure(TValue, TKey): mixed|null $by
     * [Optional] Called for every element.
     * Returned value will be used to determine the highest number.
     * @return array<TValue>
     */
    public function minMax(?Closure $by = null): array
    {
        return Arr::minMax($this, $by);
    }

    /**
     * Returns a list with two collection elements.
     * All elements in the collection evaluated to be **true** will be pushed to
     * the first collection. Elements evaluated to be **false** will be pushed to
     * the second collection.
     *
     * @param Closure(TValue, TKey): bool $condition
     * Closure to evaluate each element.
     * @return array{ static, static }
     */
    public function partition(Closure $condition): array
    {
        [$true, $false] = Arr::partition($this, $condition, $this->reindex());
        return [
            $this->instantiate($true),
            $this->instantiate($false),
        ];
    }

    /**
     * Passes `$this` to the given callback and returns the result,
     * so it can be used in a chain.
     *
     * @template TPipe
     * @param Closure($this): TPipe $callback
     * Callback which will receive $this as argument.
     * The result of the callback will be returned.
     * @return TPipe
     */
    public function pipe(Closure $callback): mixed
    {
        return $callback($this);
    }

    /**
     * Move items which match the condition to the front of the collection.
     *
     * @param Closure(TValue, TKey): bool $condition
     * User defined condition callback. The callback must return a boolean value.
     * @param int|null $limit
     * [Optional] Limits the number of items to prioritize.
     * @return static
     */
    public function prioritize(
        Closure $condition,
        ?int $limit = null,
    ): static
    {
        return $this->instantiate(Arr::prioritize($this, $condition, $limit, $this->reindex()));
    }

    /**
     * Returns the ratio of values that satisfy the `$condition`.
     * Throws `EmptyNotAllowedException` if collection is empty.
     *
     * @param Closure(TValue, TKey): bool $condition
     * User defined condition callback. The callback must return a boolean value.
     * @return float
     */
    public function ratio(Closure $condition): float
    {
        return Arr::ratio($this, $condition);
    }

    /**
     * Returns the ratio of values that satisfy the `$condition`.
     * Returns **null** if the collection is empty.
     *
     * @param Closure(TValue, TKey): bool $condition
     * User defined condition callback. The callback must return a boolean value.
     * @return float|null
     */
    public function ratioOrNull(Closure $condition): ?float
    {
        return Arr::ratioOrNull($this, $condition);
    }

    /**
     * Iteratively reduce collection to a single value by invoking
     * `$callback($reduced, $val, $key)`.
     * Throws `EmptyNotAllowedException` if the collection is empty.
     *
     * @param Closure(TValue, TValue, TKey): TValue $callback
     * First argument contains the reduced value.
     * Second argument contains the current value.
     * Third argument contains the current key.
     * @return TValue
     */
    public function reduce(Closure $callback): mixed
    {
        return Arr::reduce($this, $callback);
    }

    /**
     * Iteratively reduce collection to a single value by invoking
     * `$callback($reduced, $val, $key)`.
     * Returns `$default` if the collection is empty.
     *
     * @template TDefault
     * @param Closure(TValue, TValue, TKey): TValue $callback
     * First argument contains the reduced value.
     * Second argument contains the current value.
     * Third argument contains the current key.
     * @param TDefault $default
     * Value that is used when iterable is empty.
     * @return TValue
     */
    public function reduceOr(
        Closure $callback,
        mixed $default,
    ): mixed
    {
        return Arr::reduceOr($this, $callback, $default);
    }

    /**
     * Iteratively reduce collection to a single value by invoking
     * `$callback($reduced, $val, $key)`.
     * Returns **null** if the collection is empty.
     *
     * @param Closure(TValue, TValue, TKey): TValue $callback
     * First argument contains the reduced value.
     * Second argument contains the current value.
     * Third argument contains the current key.
     * @return TValue|null
     */
    public function reduceOrNull(Closure $callback): mixed
    {
        return Arr::reduceOrNull($this, $callback);
    }

    /**
     * Returns a new collection which contains keys and values from the collection
     * but with the `$search` value replaced with the `$replacement` value.
     *
     * @param TValue $search
     * The value to replace.
     * @param TValue $replacement
     * Replacement for the searched value.
     * @param int|null $limit
     * [Optional] Sets a limit to number of times a replacement can take place.
     * Defaults to **null**.
     * @param int &$count
     * [Optional][Reference] Sets the number of times replacements occurred.
     * Any value previously set will be reset.
     * @return static
     */
    public function replace(
        mixed $search,
        mixed $replacement,
        ?int $limit = null,
        int &$count = 0,
    ): static
    {
        return $this->instantiate(Iter::replace($this, $search, $replacement, $limit, $count));
    }

    /**
     * Returns a new collection which contain all elements of the collection
     * in reverse order.
     *
     * @return static
     */
    public function reverse(): static
    {
        return $this->instantiate(Arr::reverse($this, $this->reindex()));
    }

    /**
     * Converts the collection to an array and rotate the array to the right
     * by `$steps`. If `$steps` is a negative value, the array will rotate
     * to the left instead.
     *
     * @param int $steps
     * Number of times the key/value will be rotated.
     * @return static
     */
    public function rotate(int $steps): static
    {
        return $this->instantiate(Arr::rotate($this, $steps, $this->reindex()));
    }

    /**
     * Returns a random element from the collection.
     * Throws `EmptyNotAllowedException` if the collection is empty.
     *
     * @param Randomizer|null $randomizer
     * [Optional] Randomizer to be used.
     * Secure randomizer will be used if **null**.
     * Defaults to **null**.
     * @return TValue
     */
    public function sample(?Randomizer $randomizer = null): mixed
    {
        return Arr::sample($this, $randomizer);
    }

    /**
     * Returns a list of random elements picked from the collection.
     * If `$replace` is set to **false**, each key will be chosen only once.
     * Throws `InvalidArgumentException` if `$amount` is larger than the collection's size.
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
     * @return Vec<TValue>
     */
    public function sampleMany(
        int $amount,
        bool $replace = false,
        ?Randomizer $randomizer = null,
    ): Vec
    {
        return $this->newVec(Arr::sampleMany($this, $amount, $replace, $randomizer));
    }

    /**
     * Returns a random element from the collection.
     * Returns `$default` if the collection is empty.
     *
     * @template TDefault
     * @param TDefault $default
     * Value that is used when the collection is empty.
     * @param Randomizer|null $randomizer
     * [Optional] Randomizer to be used.
     * Default randomizer (Secure) will be used if **null**.
     * Defaults to **null**.
     * @return TValue|TDefault
     */
    public function sampleOr(
        mixed $default,
        ?Randomizer $randomizer = null,
    ): mixed
    {
        return Arr::sampleOr($this, $default, $randomizer);
    }

    /**
     * Returns a random element from the collection.
     * Returns **null** if the collection is empty.
     *
     * @param Randomizer|null $randomizer
     * [Optional] Randomizer to be used.
     * Secure randomizer will be used if **null**.
     * Defaults to **null**.
     * @return TValue|null
     */
    public function sampleOrNull(?Randomizer $randomizer = null): mixed
    {
        return Arr::sampleOrNull($this, $randomizer);
    }

    /**
     * Runs the condition though each element of the collection and will return **true**
     * if all iterations that run through the condition returned **true** or if
     * the collection is empty, **false** otherwise.
     *
     * @param Closure(TValue, TKey): bool $condition
     * User defined condition callback. The callback must return a boolean value.
     * @return bool
     */
    public function satisfyAll(Closure $condition): bool
    {
        return Arr::satisfyAll($this, $condition);
    }

    /**
     * Runs the condition though each element of the collection and will return **true**
     * if any iterations that run through the `$condition` returned **true**,
     * **false** otherwise (including empty iterable).
     *
     * @param Closure(TValue, TKey): bool $condition
     * User defined condition callback. The callback must return a boolean value.
     * @return bool
     */
    public function satisfyAny(Closure $condition): bool
    {
        return Arr::satisfyAny($this, $condition);
    }

    /**
     * Runs the condition though each element of the collection and will return **true**
     * if all the iterations that run through the `$condition` returned **false**.
     * **false** otherwise.
     *
     * @param Closure(TValue, TKey): bool $condition
     * User defined condition callback. The callback must return a boolean value.
     * @return bool
     */
    public function satisfyNone(Closure $condition): bool
    {
        return Arr::satisfyNone($this, $condition);
    }

    /**
     * Runs the condition though each element of the collection and will return **true**
     * if iterations that run through the `$condition` returned **true** only once,
     * **false** otherwise (including empty iterable).
     *
     * @param Closure(TValue, TKey): bool $condition
     * User defined condition callback. The callback must return a boolean value.
     * @return bool
     */
    public function satisfyOnce(Closure $condition): bool
    {
        return Arr::satisfyOnce($this, $condition);
    }

    /**
     * Shuffles the elements of the collection.
     *
     * @param Randomizer|null $randomizer
     * [Optional] Randomizer to be used.
     * Default randomizer (Secure) will be used if **null**.
     * Defaults to **null**.
     * @return static
     */
    public function shuffle(?Randomizer $randomizer = null): static
    {
        return $this->instantiate(Arr::shuffle($this, $this->reindex(), $randomizer));
    }

    /**
     * Returns the only element in the collection.
     * If a condition is also given, the sole element of a sequence that satisfies a specified
     * condition is returned instead.
     * Throws `InvalidArgumentException` if there are more than one element in `$iterable`.
     * Throws `NoMatchFoundException` if no condition is met.
     * Throws `EmptyNotAllowedException` if `$iterable` is empty.
     *
     * @param Closure(TValue, TKey): bool|null $condition
     * [Optional] User defined condition callback. The callback must return a boolean value.
     * Defaults to **null**.
     * @return TValue
     */
    public function single(?Closure $condition = null): mixed
    {
        return Arr::single($this, $condition);
    }

    /**
     * Returns a shallow copy of a portion of the collection into a new collection.
     *
     * @param int $offset
     * If offset is non-negative, the sequence will start at that offset.
     * If offset is negative, the sequence will start that far from the end.
     * @param int $length
     * If length is given and is positive, then the sequence will have up to that many elements in it.
     * If the iterable is shorter than the length, then only the available array elements will be present.
     * If length is given and is negative then the sequence will stop that many elements from the end.
     * If it is omitted, then the sequence will have everything from offset up until the end.
     * @return static
     */
    public function slice(int $offset, int $length = PHP_INT_MAX): static
    {
        return $this->instantiate(Iter::slice($this, $offset, $length, $this->reindex()));
    }

    /**
     * Converts the collection to an overlapping sub-slices of `$size`.
     * Also known as sliding window.
     *
     * @param int $size
     * Size of the window. Must be >= 1.
     * @return Vec<static>
     */
    public function slide(int $size): Vec
    {
        $generator = (function() use ($size) {
            foreach (Iter::slide($this, $size, $this->reindex()) as $window) {
                yield $this->instantiate($window);
            }
        })();
        return $this->newVec($generator);
    }

    /**
     * Sort the collection by value in the given order.
     *
     * @param SortOrder $order
     * Order of the sort.
     * @param Closure(TValue, TKey): mixed|null $by
     * [Optional] User defined comparison callback.
     * The value returned will be used to sort the array.
     * @param int $flag
     * Sort flag to change the behavior of the sort.
     * Defaults to `SORT_REGULAR`.
     * @return static
     */
    public function sort(
        SortOrder $order,
        ?Closure $by = null,
        int $flag = SORT_REGULAR,
    ): static
    {
        return $this->instantiate(Arr::sort($this, $order, $by, $flag, $this->reindex()));
    }

    /**
     * Sort the `$iterable` by value in ascending order.
     *
     * @param Closure(TValue, TKey): mixed|null $by
     * [Optional] User defined comparison callback.
     * The value returned will be used to sort the array.
     * @param int $flag
     * Sort flag to change the behavior of the sort.
     * Defaults to `SORT_REGULAR`.
     * @return static
     */
    public function sortAsc(
        ?Closure $by = null,
        int $flag = SORT_REGULAR,
    ): static
    {
        return $this->instantiate(Arr::sortAsc($this, $by, $flag, $this->reindex()));
    }

    /**
     * Sort the `$iterable` by value in descending order.
     *
     * @param Closure(TValue, TKey): mixed|null $by
     * [Optional] User defined comparison callback.
     * The value returned will be used to sort the array.
     * @param int $flag
     * Sort flag to change the behavior of the sort.
     * Defaults to `SORT_REGULAR`.
     * @return static
     */
    public function sortDesc(
        ?Closure $by = null,
        int $flag = SORT_REGULAR,
    ): static
    {
        return $this->instantiate(Arr::sortDesc($this, $by, $flag, $this->reindex()));
    }

    /**
     * Sorts the collection by value using the provided `$comparator` function.
     *
     * @param Closure(TValue, TValue): int $comparator
     * The comparison function to use.
     * Utilize the spaceship operator (`<=>`) to easily compare two values.
     * @return static
     */
    public function sortWith(Closure $comparator): static
    {
        return $this->instantiate(Arr::sortWith($this, $comparator, $this->reindex()));
    }

    /**
     * Splits the collection right after the index where `$condition` returned **true**.
     *
     * @param Closure(TValue, TKey): bool $condition
     * User defined condition callback. The callback must return a boolean value.
     * @return Vec<static>
     */
    public function splitAfter(Closure $condition): Vec
    {
        return $this->newVec(array_map(
            $this->instantiate(...),
            Arr::splitAfter($this, $condition, $this->reindex()),
        ));
    }

    /**
     * Splits the collection after the given `$index`.
     *
     * @param int $index
     * The index where the `$iterable` will be split starting from 0.
     * Negative index will count from the end.
     * @return Vec<static>
     */
    public function splitAfterIndex(int $index): Vec
    {
        return $this->newVec(array_map(
            $this->instantiate(...),
            Arr::splitAfterIndex($this, $index, $this->reindex()),
        ));
    }

    /**
     * Splits the collection right before the index where `$condition` returned **true**.
     *
     * @param Closure(TValue, TKey): bool $condition
     * User defined condition callback. The callback must return a boolean value.
     * @return Vec<static>
     */
    public function splitBefore(Closure $condition): Vec
    {
        return $this->newVec(array_map(
            $this->instantiate(...),
            Arr::splitBefore($this, $condition, $this->reindex()),
        ));
    }

    /**
     * Splits the collection before the given `$index`.
     *
     * @param int $index
     * The index where the `$iterable` will be split starting from 0.
     * Negative index will count from the end.
     * @return Vec<static>
     */
    public function splitBeforeIndex(int $index): Vec
    {
        return $this->newVec(array_map(
            $this->instantiate(...),
            Arr::splitBeforeIndex($this, $index, $this->reindex()),
        ));
    }

    /**
     * Splits the collection into the given size and return it as Vec.
     *
     * @param int $parts
     * Number of parts to split into.
     * @return Vec<static>
     */
    public function splitEvenly(int $parts): Vec
    {
        return $this->newVec(array_map(
            $this->instantiate(...),
            Arr::splitEvenly($this, $parts, $this->reindex()),
        ));
    }

    /**
     * Returns **true** if the collection starts with the given `$values`, **false** otherwise.
     *
     * @param iterable<int, TValue> $values
     * Values to be searched.
     * @return bool
     */
    public function startsWith(
        iterable $values,
    ): bool
    {
        return Arr::startsWith($this, $values);
    }

    /**
     * Returns a new instance with every nth elements dropped.
     *
     * @param int $nth
     * Nth value to drop. Must be >= 1.
     * @return static
     */
    public function takeEvery(int $nth): static
    {
        return $this->instantiate(Arr::takeEvery($this, $nth, $this->reindex()));
    }

    /**
     * Take the first n elements from the collection and return a new instance
     * with those elements.
     *
     * @param int $amount
     * Amount of elements to take. Must be >= 0.
     * @return static
     */
    public function takeFirst(int $amount): static
    {
        return $this->instantiate(Iter::takeFirst($this, $amount));
    }

    /**
     * Returns a new collection that contains all elements where the `$condition` returned **true**.
     *
     * Iterable to be traversed.
     * @param Closure(TValue, TKey): bool $condition
     * A condition that should return a boolean.
     * @return static
     */
    public function takeIf(Closure $condition): static
    {
        return $this->instantiate(Iter::takeIf($this, $condition, $this->reindex()));
    }

    /**
     * Iterates over each element in $iterable and takes only the elements that are instances of the given class.
     *
     * @template TClass of object
     * @param class-string<TClass> $class
     * Class name to check against.
     * @return static
     */
    public function takeInstanceOf(string $class): static
    {
        // @phpstan-ignore argument.type
        return $this->instantiate(Iter::takeInstanceOf($this, $class, $this->reindex()));
    }

    /**
     * Take the last n elements from the collection and return a new instance
     * with those elements.
     *
     * @param int $amount
     * Amount of items to be dropped from the end. Must be >= 0.
     * @return static
     */
    public function takeLast(int $amount): static
    {
        return $this->instantiate(Arr::takeLast($this, $amount, $this->reindex()));
    }

    /**
     * Takes elements in the collection until `$condition` returns **true**.
     *
     * @param Closure(TValue, TKey): bool $condition
     * A break condition callback that should return **false** to stop the
     * taking of elements from the collection.
     * @return static
     */
    public function takeUntil(Closure $condition): static
    {
        return $this->instantiate(Iter::takeUntil($this, $condition));
    }

    /**
     * Takes elements in the collection while `$condition` returns **true**.
     *
     * @param Closure(TValue, TKey): bool $condition
     * A break condition callback that should return **false** to stop the
     * taking of elements from the collection.
     * @return static
     */
    public function takeWhile(Closure $condition): static
    {
        return $this->instantiate(Iter::takeWhile($this, $condition));
    }

    /**
     * Converts the collection to an array recursively up to the given `$depth`.
     *
     * @param int<1, max> $depth
     * [Optional] Defaults to INT_MAX
     * @return array<TKey, TValue>
     */
    public function toArray(int $depth = PHP_INT_MAX): array
    {
        return $this->asArrayRecursive($this, $depth, true);
    }

    /**
     * Converts the collection to a JSON string.
     *
     * @param bool $pretty
     * [Optional] Whether to format the JSON as human-readable format.
     * Defaults to **false**.
     * @return string
     */
    public function toJson(bool $pretty = false): string
    {
        return Json::encode($this, $pretty);
    }

    /**
     * Removes duplicate values from `$iterable` and returns it as an array.
     *
     * This differs from `array_unique` in that, this does not do a
     * string conversion before comparing.
     * For example, `array_unique([1, true])` will result in: `[1]` but
     * doing `Arr::unique([1, true])` will result in: `[1, true]`.
     *
     * @param Closure(TValue, TKey): bool|null $by
     * [Optional] Called for every element in `$iterable`.
     * Returned value will be used to check for duplicates.
     * [Optional] Defaults to **null**.
     * @return static
     */
    public function unique(?Closure $by = null): static
    {
        return $this->instantiate(Arr::unique($this, $by, $this->reindex()));
    }

    /**
     * Calls `$callback` for every element in the collection if `$bool`
     * is **true**, calls `$fallback` otherwise.
     *
     * @template TReturn
     * @param bool|Closure($this): bool $bool
     * Bool or callback to determine whether to execute `$callback` or `$fallback`.
     * @param Closure($this): TReturn $callback
     * Callback to be called if `$bool` is **true**.
     * @param Closure($this): TReturn|null $fallback
     * [Optional] Callback to be called if `$bool` is **false**.
     * @return TReturn
     */
    public function when(
        bool|Closure $bool,
        Closure $callback,
        ?Closure $fallback = null,
    ): mixed
    {
        if ($bool instanceof Closure) {
            $bool = $bool($this);
            if (!is_bool($bool)) {
                $type = gettype($bool);
                throw new TypeMismatchException("Expected \$bool (Closure) to return bool, {$type} given.", [
                    'this' => $this,
                    'bool' => $bool,
                    'callback' => $callback,
                    'fallback' => $fallback,
                ]);
            }
        }

        $fallback ??= static fn($self): static => $self;

        return $bool
            ? $callback($this)
            : $fallback($this);
    }

    /**
     * Calls `$callback` for every element in the collection if collection is empty,
     * calls `$fallback` otherwise.
     *
     * @template TReturn
     * @param Closure($this): TReturn $callback
     * Callback to be called if `$bool` is **true**.
     * @param Closure($this): TReturn|null $fallback
     * [Optional] Callback to be called if `$bool` is **false**.
     * @return TReturn
     */
    public function whenEmpty(
        Closure $callback,
        ?Closure $fallback = null,
    ): mixed
    {
        return $this->when($this->isEmpty(), $callback, $fallback);
    }

    /**
     * Calls `$callback` for every element in the collection if collection is not
     * empty, calls `$fallback` otherwise.
     *
     * @template TReturn
     * @param Closure($this): TReturn $callback
     * Callback to be called if `$bool` is **true**.
     * @param Closure($this): TReturn|null $fallback
     * [Optional] Callback to be called if `$bool` is **false**.
     * @return TReturn
     */
    public function whenNotEmpty(
        Closure $callback,
        ?Closure $fallback = null,
    ): mixed
    {
        return $this->when($this->isNotEmpty(), $callback, $fallback);
    }

    /**
     * Returns a new instance with the specified `$value` excluded.
     *
     * @param TValue $value
     * Value to be excluded.
     * @return static
     */
    public function without(mixed $value): static
    {
        return $this->instantiate(Arr::without($this, $value, $this->reindex()));
    }

    /**
     * @param iterable<TKey, TValue> $items
     * @param int $depth
     * @param bool $validate
     * @return array<TKey, mixed>
     */
    protected function asArrayRecursive(
        iterable $items,
        int $depth,
        bool $validate = false,
    ): array
    {
        if ($validate && $depth < 1) {
            throw new InvalidArgumentException("Expected: \$depth >= 1. Got: {$depth}.", [
                'this' => $this,
                'items' => $items,
                'depth' => $depth,
                'validate' => $validate,
            ]);
        }

        return Arr::map($items, function($item) use ($depth) {
            if (is_iterable($item) && $depth > 1) {
                return $this->asArrayRecursive($item, $depth - 1);
            }
            return $item;
        });
    }

    /**
     * @template TNewKey of array-key
     * @template TNewValue
     * @param iterable<TNewKey, TNewValue> $iterable
     * @return Map<TNewKey, TNewValue>
     */
    protected function newMap(iterable $iterable): Map
    {
        return new Map($iterable);
    }

    /**
     * @template TNewValue
     * @param iterable<int, TNewValue> $iterable
     * @return Vec<TNewValue>
     */
    protected function newVec(iterable $iterable): Vec
    {
        return new Vec($iterable);
    }
}
