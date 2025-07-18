<?php declare(strict_types=1);

namespace Kirameki\Collections\Utils;

use Closure;
use JsonException;
use Kirameki\Collections\Exceptions\CountMismatchException;
use Kirameki\Collections\Exceptions\DuplicateKeyException;
use Kirameki\Collections\Exceptions\EmptyNotAllowedException;
use Kirameki\Collections\Exceptions\ExcessKeyException;
use Kirameki\Collections\Exceptions\IndexOutOfBoundsException;
use Kirameki\Collections\Exceptions\InvalidElementException;
use Kirameki\Collections\Exceptions\InvalidKeyException;
use Kirameki\Collections\Exceptions\MissingKeyException;
use Kirameki\Collections\Exceptions\NoMatchFoundException;
use Kirameki\Collections\SortOrder;
use Kirameki\Core\Exceptions\InvalidArgumentException;
use Kirameki\Core\Exceptions\TypeMismatchException;
use Kirameki\Core\Exceptions\UnreachableException;
use Kirameki\Core\Func;
use Kirameki\Core\Type;
use Random\Randomizer;
use function array_diff_ukey;
use function array_fill;
use function array_intersect;
use function array_intersect_key;
use function array_is_list;
use function array_key_exists;
use function array_keys;
use function array_map;
use function array_pop;
use function array_push;
use function array_reverse;
use function array_search;
use function array_shift;
use function array_splice;
use function array_udiff;
use function array_unshift;
use function array_values;
use function arsort;
use function asort;
use function ceil;
use function count;
use function current;
use function end;
use function get_resource_id;
use function gettype;
use function http_build_query;
use function in_array;
use function is_array;
use function is_bool;
use function is_countable;
use function is_float;
use function is_int;
use function is_iterable;
use function is_nan;
use function is_null;
use function is_object;
use function is_resource;
use function is_string;
use function iterator_to_array;
use function json_encode;
use function key;
use function krsort;
use function ksort;
use function max;
use function prev;
use function spl_object_id;
use function uasort;
use function uksort;
use const JSON_THROW_ON_ERROR;
use const PHP_INT_MAX;
use const PHP_QUERY_RFC3986;
use const SORT_REGULAR;

final class Arr
{
    private const array EMPTY = [];

    /**
     * Default randomizer that will be used on `shuffle`, `sample`, `sampleMany`.
     *
     * @var Randomizer|null
     */
    private static ?Randomizer $defaultRandomizer = null;

    /**
     * Only used for `self::miss()`.
     */
    private function __construct()
    {
    }

    /**
     * Append value(s) to the end of `$iterable`.
     * The iterable must be convertible to a list.
     * Will throw `TypeMismatchException` if map is given.
     *
     * Example:
     * ```php
     * Arr::append([1, 2], 3); // [1, 2, 3]
     * Arr::append([1, 2], 3, 4); // [1, 2, 3, 4]
     * ```
     *
     * @template T
     * @param iterable<int, T> &$iterable
     * Iterable which the value is getting appended.
     * @param T ...$values
     * Value(s) to be appended to the array.
     * @return list<T>
     */
    public static function append(
        iterable $iterable,
        mixed ...$values,
    ): array
    {
        $array = self::from($iterable);
        if (!array_is_list($array)) {
            throw new TypeMismatchException('$array must be a list, map given.', [
                'iterable' => $iterable,
                'values' => $values,
            ]);
        }
        if (!array_is_list($values)) {
            $values = array_values($values);
        }
        array_push($array, ...$values);
        return $array;
    }

    /**
     * Returns the item at the given index.
     * Throws `IndexOutOfBoundsException` if the index does not exist.
     *
     * Example:
     * ```php
     * Arr::at([6, 7], 1); // 7
     * Arr::at([6, 7], -1); // 7
     * Arr::at(['a' => 1, 'b' => 2], 0); // 1
     * Arr::at([6], 1); // IndexOutOfBoundsException
     * ```
     *
     * @template TKey of array-key
     * @template TValue
     * @param iterable<TKey, TValue> $iterable
     * Iterable to be traversed.
     * @param int $index
     * Index of iterable starting with 0. Negative index will traverse from tail.
     * @return TValue
     */
    public static function at(
        iterable $iterable,
        int $index,
    ): mixed
    {
        $result = self::atOr($iterable, $index, self::miss());

        if ($result instanceof self) {
            $count = self::count($iterable);
            throw new IndexOutOfBoundsException("Size: $count index: $index.", [
                'iterable' => $iterable,
                'index' => $index,
                'count' => $count,
            ]);
        }

        return $result;
    }

    /**
     * Returns the item at the given index.
     * Returns `$default` if the given index does not exist.
     *
     * Example:
     * ```php
     * Arr::atOr([6, 7], 1); // 7
     * Arr::atOr([6, 7], -1); // 7
     * Arr::atOr(['a' => 1, 'b' => 2], 0); // 1
     * ```
     *
     * @template TKey of array-key
     * @template TValue
     * @template TDefault
     * @param iterable<TKey, TValue> $iterable
     * Iterable to be traversed.
     * @param int $index
     * Index of iterable starting with 0. Negative index will traverse from tail.
     * @param TDefault $default
     * Value that is used when the given index did not exist.
     * @return TValue|TDefault
     */
    public static function atOr(
        iterable $iterable,
        int $index,
        mixed $default,
    ): mixed
    {
        if ($index < 0) {
            $iterable = self::from($iterable);
            $index = count($iterable) + $index;
        }

        // If the iterable is a list, we can access the index directly.
        if (is_array($iterable) && array_is_list($iterable)) {
            return $iterable[$index] ?? $default;
        }

        $count = 0;
        foreach ($iterable as $val) {
            if ($count === $index) {
                return $val;
            }
            ++$count;
        }

        return $default;
    }

    /**
     * Returns the item at the given index.
     *
     * Example:
     * ```php
     * Arr::atOrNull([6, 7], 1); // 7
     * Arr::atOrNull([6, 7], -1); // 7
     * Arr::atOrNull(['a' => 1, 'b' => 2], 0); // 1
     * Arr::atOrNull([6], 1); // null
     * ```
     *
     * @template TKey of array-key
     * @template TValue
     * @param iterable<TKey, TValue> $iterable
     * Iterable to be traversed.
     * @param int $index
     * Index of iterable starting with 0. Negative index will traverse from tail.
     * @return TValue|null
     */
    public static function atOrNull(
        iterable $iterable,
        int $index,
    ): mixed
    {
        return self::atOr($iterable, $index, null);
    }

    /**
     * Get the average of the elements inside `$iterable`.
     * The elements must be af type int or float.
     * Throws `InvalidElementException` if the `$iterable` is empty.
     * Throws `EmptyNotAllowedException` if `$iterable` contains NAN.
     * Example:
     * ```php
     * Arr::average([]); // 0
     * Arr::average([1, 2, 3]); // 2
     * Arr::average([0.1, 0.1]); // 0.1
     * ```
     *
     * @template TKey of array-key
     * @template TValue of float|int
     * @param iterable<TKey, TValue> $iterable
     * Iterable to be traversed.
     * @return float
     */
    public static function average(
        iterable $iterable,
    ): float
    {
        $average = self::averageOrNull($iterable);

        if ($average === null) {
            throw new EmptyNotAllowedException('$iterable must contain at least one element.', [
                'iterable' => $iterable,
            ]);
        }

        return $average;
    }

    /**
     * Get the average of the elements inside `$iterable`.
     * The elements must be af type int or float.
     * If `$iterable` is empty, **null** will be returned.
     * Throws `InvalidElementException` if iterable contains NAN.
     *
     * Example:
     * ```php
     * Arr::averageOrNull([]); // null
     * Arr::averageOrNull([1, 2, 3]); // 2
     * Arr::averageOrNull([0.1, 0.1]); // 0.1
     * ```
     *
     * @template TKey of array-key
     * @template TValue of float|int
     * @param iterable<TKey, TValue> $iterable
     * Iterable to be traversed.
     * @return float|null
     */
    public static function averageOrNull(
        iterable $iterable,
    ): float|null
    {
        $size = 0;
        $sum = 0.0;
        foreach ($iterable as $val) {
            $sum += $val;
            ++$size;
        }

        if ($size === 0) {
            return null;
        }

        if (is_nan($sum)) {
            throw new InvalidElementException('$iterable cannot contain NAN.', [
                'iterable' => $iterable,
            ]);
        }

        return $sum / $size;
    }

    /**
     * Splits the iterable into chunks of new arrays.
     *
     * Example:
     * ```php
     * Arr::chunk([1, 2, 3], 2); // [[1, 2], [3]]
     * ```
     *
     * @template TKey of array-key
     * @template TValue
     * @param iterable<TKey, TValue> $iterable
     * Iterable to be traversed.
     * @param int $size
     * Size of each chunk.
     * @param bool|null $reindex
     * Result will be re-indexed if **true**.
     * If **null**, the result will be re-indexed only if it's a list.
     * Defaults to **null**.
     * @return list<array<TKey, TValue>>
     */
    public static function chunk(
        iterable $iterable,
        int $size,
        ?bool $reindex = null,
    ): array
    {
        $array = self::from($iterable);
        $reindex ??= array_is_list($array);
        return Arr::values(Iter::chunk($array, $size, $reindex));
    }

    /**
     * Removes all elements in the given array (reference).
     *
     * Example:
     * ```php
     * $array = [1, 2]; Arr::clear($array); // []
     * $array = ['a' => 1, 'b' => 2]; Arr::clear($array); // []
     * ```
     *
     * @param array<array-key, mixed> $array
     * @param-out array<array-key, mixed> $array
     * Reference of array to be cleared.
     * @return void
     */
    public static function clear(
        array &$array,
    ): void
    {
        $array = [];
    }

    /**
     * Returns the first non-null value in the array.
     * Throws `InvalidArgumentException` if `$iterable` is empty or if all elements are **null**.
     *
     * Example:
     * ```php
     * Arr::coalesce([null, null, 1]); // 1
     * Arr::coalesce([null, null]); // InvalidArgumentException
     * ```
     *
     * @template TKey of array-key
     * @template TValue
     * @param iterable<TKey, TValue> $iterable
     * Iterable to be traversed.
     * @return TValue
     */
    public static function coalesce(
        iterable $iterable,
    ): mixed
    {
        $result = self::coalesceOrNull($iterable);

        if ($result === null) {
            throw new NoMatchFoundException('Non-null value could not be found.', [
                'iterable' => $iterable,
            ]);
        }

        return $result;
    }

    /**
     * Returns the first non-null value in the array.
     * Returns **null** if `$iterable` is empty or if all elements are **null**.
     *
     * Example:
     * ```php
     * Arr::coalesceOrNull([null, null, 1]); // 1
     * Arr::coalesceOrNull([null, null]); // null
     * Arr::coalesceOrNull([]); // null
     * ```
     *
     * @template TKey of array-key
     * @template TValue
     * @param iterable<TKey, TValue> $iterable
     * Iterable to be traversed.
     * @return TValue|null
     */
    public static function coalesceOrNull(
        iterable $iterable,
    ): mixed
    {
        foreach ($iterable as $val) {
            if ($val !== null) {
                return $val;
            }
        }
        return null;
    }

    /**
     * Returns **true** if value exists in `$iterable`, **false** otherwise.
     *
     * Example:
     * ```php
     * Arr::contains([1, 2], 2); // true
     * Arr::contains([1, 2], 3); // false
     * Arr::contains(['a' => 1], 1); // true
     * Arr::contains(['a' => 1], 'a'); // false
     * ```
     *
     * @template TKey of array-key
     * @template TValue
     * @param iterable<TKey, TValue> $iterable
     * Iterable to be traversed.
     * @param mixed $value
     * Value to be searched.
     * @return bool
     */
    public static function contains(
        iterable $iterable,
        mixed $value,
    ): bool
    {
        // in_array is much faster than iterating
        if (is_array($iterable)) {
            return in_array($value, $iterable, true);
        }

        foreach ($iterable as $val) {
            if ($val === $value) {
                return true;
            }
        }
        return false;
    }

    /**
     * Returns **true** if `$iterable` contains all the provided `$values`,
     * **false** otherwise.
     *
     * Example:
     * ```php
     * Arr::containsAll([1, 2, 3], [2, 3]); // true
     * Arr::containsAll([1, 2, 3], [1, 4]); // false
     * Arr::containsAll([1], []); // true
     * ```
     *
     * @template TKey of array-key
     * @template TValue
     * @param iterable<TKey, TValue> $iterable
     * Iterable to be traversed.
     * @param iterable<int, TValue> $values
     * Values to be searched.
     * @return bool
     */
    public static function containsAll(
        iterable $iterable,
        iterable $values,
    ): bool
    {
        $values = self::unique(self::from($values));

        if (count($values) === 0) {
            return true;
        }

        foreach ($iterable as $item) {
            $key = array_search($item, $values, true);
            if ($key !== false) {
                unset($values[$key]);
                if (count($values) === 0) {
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * Returns **true** if `$iterable` contains all the provided `$keys`,
     * **false** otherwise.
     *
     * Example:
     * ```php
     * Arr::containsAllKeys(['a' => 1, 'b' => 2], ['a', 'b']); // true
     * Arr::containsAllKeys(['a' => 1, 'b' => 2], ['a', 'c']); // false
     * Arr::containsAllKeys([1], []); // true
     * ```
     *
     * @template TKey of array-key
     * @template TValue
     * @param iterable<TKey, TValue> $iterable
     * Iterable to be traversed.
     * @param iterable<int, TKey> $keys
     * Keys to be searched.
     * @return bool
     */
    public static function containsAllKeys(
        iterable $iterable,
        iterable $keys,
    ): bool
    {
        $array = self::from($iterable);
        foreach ($keys as $key) {
            if (!array_key_exists($key, $array)) {
                return false;
            }
        }
        return true;
    }

    /**
     * Returns **true** if `$iterable` contains any of the provided `$values`,
     * **false** otherwise.
     *
     * Example:
     * ```php
     * Arr::containsAny([1, 2, 3], [2]); // true
     * Arr::containsAny([1, 2, 3], []) // false
     * ```
     *
     * @template TKey of array-key
     * @template TValue
     * @param iterable<TKey, TValue> $iterable
     * Iterable to be traversed.
     * @param iterable<int, TValue> $values
     * Values to be searched.
     * @return bool
     */
    public static function containsAny(
        iterable $iterable,
        iterable $values,
    ): bool
    {
        $values = self::from($values);
        foreach ($iterable as $item) {
            if (in_array($item, $values, true)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Returns **true** if `$iterable` contains any of the provided `$keys`,
     * **false** otherwise.
     *
     * Example:
     * ```php
     * Arr::containsAnyKeys(['a' => 1, 'b' => 2], ['a', 'c']); // true
     * Arr::containsAnyKeys(['a' => 1, 'b' => 2], ['c', 'd']); // false
     * Arr::containsAnyKeys(['a' => 1], []); // false
     * ```
     *
     * @template TKey of array-key
     * @template TValue
     * @param iterable<TKey, TValue> $iterable
     * Iterable to be traversed.
     * @param iterable<int, TKey> $keys
     * Values to be searched.
     * @return bool
     */
    public static function containsAnyKeys(
        iterable $iterable,
        iterable $keys,
    ): bool
    {
        $array = self::from($iterable);
        foreach ($keys as $key) {
            if (array_key_exists($key, $array)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Returns **true** if a given key exists within iterable, **false** otherwise.
     *
     * Example:
     * ```php
     * Arr::containsKey([1, 2], 0); // true
     * Arr::containsKey([1, 2], 2); // false
     * Arr::containsKey(['a' => 1], 'a'); // true
     * Arr::containsKey(['a' => 1], 1); // false
     * ```
     *
     * @template TKey of array-key
     * @param iterable<TKey, mixed> $iterable
     * Iterable to be traversed.
     * @param array-key $key
     * Key to check for in `$iterable`.
     * @return bool
     */
    public static function containsKey(
        iterable $iterable,
        int|string $key,
    ): bool
    {
        return array_key_exists($key, self::from($iterable));
    }

    /**
     * Returns **true** if `$iterable` contains none of the provided `$values`,
     * **false** otherwise.
     *
     * Example:
     * ```php
     * Arr::containsNone([1, 2], [3]); // true
     * Arr::containsNone([1, 2], [2]); // false
     * ```
     *
     * @template TKey of array-key
     * @template TValue
     * @param iterable<TKey, TValue> $iterable
     * Iterable to be traversed.
     * @param iterable<int, TValue> $values
     * Values to be searched.
     * @return bool
     */
    public static function containsNone(
        iterable $iterable,
        iterable $values,
    ): bool
    {
        $values = self::from($values);
        foreach ($iterable as $item) {
            if (in_array($item, $values, true)) {
                return false;
            }
        }
        return true;
    }

    /**
     * Returns **true** if `$iterable` contains the given slice of `$values`,
     * **false** otherwise.
     *
     * Example:
     * ```php
     * Arr::containsSlice([1, 2, 3], [2, 3]); // true
     * Arr::containsSlice([1, 2, 3, 4], [2, 4]); // false
     * ```
     *
     * @template TKey of array-key
     * @template TValue
     * @param iterable<TKey, TValue> $iterable
     * Iterable to be traversed.
     * @param iterable<int, TValue> $values
     * Values to be searched.
     * @return bool
     */
    public static function containsSlice(
        iterable $iterable,
        iterable $values,
    ): bool
    {
        $array = self::values($iterable);
        $values = self::values($values);

        if ($values === []) {
            return true;
        }

        for ($i = 0, $aCount = count($array); $i < $aCount; $i++) {
            if ($array[$i] === $values[0]) {
                for ($j = 0, $vCount = count($values); $j < $vCount; $j++) {
                    if (($i + $j) > ($aCount - 1)) {
                        break 2;
                    }
                    if ($array[$i + $j] !== $values[$j]) {
                        continue 2;
                    }
                }
                return true;
            }
        }
        return $array === $values;
    }

    /**
     * Counts all the elements in `$iterable`.
     * If `$condition` is provided, it will only increase the count if the condition returns **true**.
     *
     * Example:
     * ```php
     * Arr::count(['a', 'b']); // 2
     * Arr::count([1, 2], fn(int $n): bool => ($n % 2) === 0); // 1
     * ```
     *
     * @template TKey of array-key
     * @template TValue
     * @param iterable<TKey, mixed> $iterable
     * Iterable to be traversed.
     * @param Closure(TValue, TKey): bool|null $condition
     * [Optional] Condition to determine if given item should be counted.
     * Defaults to **null**.
     * @return int<0, max>
     */
    public static function count(
        iterable $iterable,
        ?Closure $condition = null,
    ): int
    {
        if ($condition === null && is_countable($iterable)) {
            return count($iterable);
        }

        $count = 0;
        $condition ??= Func::true();
        foreach ($iterable as $key => $val) {
            if (self::verifyBool($condition, $key, $val)) {
                ++$count;
            }
        }
        return $count;
    }

    /**
     * Compares the keys from `$iterable1` against the values from `$iterable2` and returns the difference.
     *
     * Example:
     * ```php
     * Arr::diff([], [1]); // []
     * Arr::diff([1], []); // [1]
     * Arr::diff([1, 2], [2, 3]); // [1]
     * ```
     *
     * @template TKey of array-key
     * @template TValue
     * @param iterable<TKey, TValue> $iterable1
     * Iterable to be traversed.
     * @param iterable<TKey, TValue> $iterable2
     * Iterable to be compared with the first iterable.
     * @param Closure(TValue, TValue): int<-1, 1>|null $by
     * [Optional] Callback which can be used for comparison of items in both iterables.
     * @param bool|null $reindex
     * [Optional] Result will be re-indexed if **true**.
     * If **null**, the result will be re-indexed only if it's a list.
     * Defaults to **null**.
     * @return array<TKey, TValue>
     */
    public static function diff(
        iterable $iterable1,
        iterable $iterable2,
        ?Closure $by = null,
        ?bool $reindex = null,
    ): array
    {
        $array1 = self::from($iterable1);
        $array2 = self::from($iterable2);
        $by ??= Func::spaceship();
        $reindex ??= array_is_list($array1);

        $result = array_udiff($array1, $array2, $by);

        return $reindex
            ? array_values($result)
            : $result;
    }

    /**
     * Compares the keys from `$iterable1` against the keys from `$iterable2` and returns the difference.
     *
     * Example:
     * ```php
     * Arr::diffKeys([0 => 1, 1 => 2], [0 => 3]); // [2]
     * Arr::diffKeys(['a' => 1, 'b' => 2], ['a' => 2, 'c' => 3]); // ['b' => 2]
     * ```
     *
     * @template TKey of array-key
     * @template TValue
     * @param iterable<TKey, TValue> $iterable1
     * Iterable to be traversed.
     * @param iterable<TKey, TValue> $iterable2
     * Iterable to be compared with the first iterable.
     * @param Closure(TKey, TKey): int|null $by
     * [Optional] Callback which can be used for comparison of items in both iterables.
     * @param bool|null $reindex
     * [Optional] Result will be re-indexed if **true**.
     * If **null**, the result will be re-indexed only if it's a list.
     * Defaults to **null**.
     * @return array<TKey, TValue>
     */
    public static function diffKeys(
        iterable $iterable1,
        iterable $iterable2,
        ?Closure $by = null,
        ?bool $reindex = null,
    ): array
    {
        $array1 = self::from($iterable1);
        $array2 = self::from($iterable2);
        $by ??= Func::spaceship();
        $reindex ??= array_is_list($array1);

        $result = array_diff_ukey($array1, $array2, $by);

        return $reindex
            ? array_values($result)
            : $result;
    }

    /**
     * Returns **false** if value exists in iterable, **true** otherwise.
     *
     * Example:
     * ```php
     * Arr::doesNotContain([1, 2], 2); // false
     * Arr::doesNotContain([1, 2], 3); // true
     * Arr::doesNotContain(['a' => 1], 1); // false
     * Arr::doesNotContain(['a' => 1], 'a'); // true
     * ```
     *
     * @template TKey of array-key
     * @template TValue
     * @param iterable<TKey, TValue> $iterable
     * Iterable to be traversed.
     * @param mixed $value
     * Value to be searched.
     * @return bool
     */
    public static function doesNotContain(
        iterable $iterable,
        mixed $value,
    ): bool
    {
        return !self::contains($iterable, $value);
    }

    /**
     * Returns **false** if a given key exists within iterable, **true** otherwise.
     *
     * Example:
     * ```php
     * Arr::notContainsKey([1, 2], 0); // false
     * Arr::notContainsKey([1, 2], 2); // true
     * Arr::notContainsKey(['a' => 1], 'a'); // false
     * Arr::notContainsKey(['a' => 1], 1); // true
     * ```
     *
     * @template TKey of array-key
     * @param iterable<TKey, mixed> $iterable
     * Iterable to be traversed.
     * @param array-key $key
     * Key to be searched.
     * @return bool
     */
    public static function doesNotContainKey(
        iterable $iterable,
        int|string $key,
    ): bool
    {
        return !self::containsKey($iterable, $key);
    }

    /**
     * Drop every `$nth` elements from `$iterable`.
     *
     * @template TKey of array-key
     * @template TValue
     * @param iterable<TKey, TValue> $iterable
     * Iterable to be traversed.
     * @param int $nth
     * Nth value to drop. Must be >= 1.
     * @return array<TKey, TValue>
     */
    public static function dropEvery(
        iterable $iterable,
        int $nth,
        ?bool $reindex = null,
    ): array
    {
        if ($nth <= 0) {
            throw new InvalidArgumentException("Expected: \$nth >= 1. Got: {$nth}.", [
                'iterable' => $iterable,
                'nth' => $nth,
                'reindex' => $reindex,
            ]);
        }

        $i = 0;
        return self::dropIf($iterable, static function() use (&$i, $nth) {
            ++$i;
            return $i % $nth === 0;
        }, $reindex);
    }

    /**
     * Drop the first n elements from `$iterable`.
     *
     * Example:
     * ```php
     * Arr::dropFirst([1, 1, 2], 1); // [1, 2]
     * Arr::dropFirst(['a' => 1], 3); // []
     * Arr::dropFirst(['a' => 1, 'b' => 2], 1); // ['b' => 2]
     * ```
     *
     * @template TKey of array-key
     * @template TValue
     * @param iterable<TKey, TValue> $iterable
     * Iterable to be traversed.
     * @param int $amount
     * Amount of items to be dropped from the front. Must be >= 0.
     * @param bool|null $reindex
     * [Optional] Result will be re-indexed if **true**.
     * If **null**, the result will be re-indexed only if it's a list.
     * Defaults to **null**.
     * @return array<TKey, TValue>
     */
    public static function dropFirst(
        iterable $iterable,
        int $amount,
        ?bool $reindex = null,
    ): array
    {
        $array = self::from($iterable);
        $reindex ??= array_is_list($array);
        return iterator_to_array(Iter::dropFirst($array, $amount, $reindex));
    }

    /**
     * Iterates over each element in iterable and passes them to the callback function.
     * If the callback function returns **false** the element is passed on to the new array.
     *
     * Example:
     * ```php
     * Arr::dropIf([null, '', 1], empty(...)); // [1]
     * Arr::dropIf(['a' => true, 'b' => 1], fn($v) => $v === 1); // ['a' => true]
     * ```
     *
     * @template TKey of array-key
     * @template TValue
     * @param iterable<TKey, TValue> $iterable
     * Iterable to be traversed.
     * @param Closure(TValue, TKey): bool $condition
     * User defined condition callback. The callback must return a boolean value.
     * @param bool|null $reindex
     * [Optional] Result will be re-indexed if **true**.
     * If **null**, the result will be re-indexed only if it's a list.
     * Defaults to **null**.
     * @return array<TKey, TValue>
     */
    public static function dropIf(
        iterable $iterable,
        Closure $condition,
        ?bool $reindex = null,
    ): array
    {
        $array = self::from($iterable);
        $reindex ??= array_is_list($array);
        return iterator_to_array(Iter::dropIf($array, $condition, $reindex));
    }

    /**
     * Returns a new array with the given keys removed from `$iterable`.
     * Missing keys will be ignored.
     * If `$safe` is set to **true**, `MissingKeyException` will be thrown
     * if a key does not exist in `$iterable`.
     *
     * Example:
     * ```php
     * Arr::dropKeys(['a' => 1, 'b' => 2], ['a']); // ['b' => 2]
     * Arr::dropKeys([1, 2, 3], [0, 2], reindex: true); // [2]
     * ```
     *
     * @template TKey of array-key
     * @template TValue
     * @param iterable<TKey, TValue> $iterable
     * Iterable to be traversed.
     * @param iterable<int, array-key> $keys
     * Keys to be excluded.
     * @param bool $safe
     * [Optional] If this is set to **true**, `MissingKeyException` will be
     * thrown if key does not exist in `$iterable`.
     * If set to **false**, non-existing keys will be filled with **null**.
     * Defaults to **true**.
     * @param bool|null $reindex
     * [Optional] Result will be re-indexed if **true**.
     * If **null**, the result will be re-indexed only if it's a list.
     * Defaults to **null**.
     * @return array<TKey, TValue>
     */
    public static function dropKeys(
        iterable $iterable,
        iterable $keys,
        bool $safe = true,
        ?bool $reindex = null,
    ): array
    {
        $copy = self::from($iterable);
        $reindex ??= array_is_list($copy);

        $missingKeys = [];
        foreach ($keys as $key) {
            if (array_key_exists($key, $copy)) {
                unset($copy[$key]);
            } elseif ($safe) {
                $missingKeys[] = $key;
            }
        }

        if ($safe && self::isNotEmpty($missingKeys)) {
            throw new MissingKeyException($missingKeys, [
                'iterable' => $iterable,
                'givenKeys' => $keys,
                'missingKeys' => $missingKeys,
            ]);
        }

        return $reindex
            ? array_values($copy)
            : $copy;
    }

    /**
     * Drop the last n elements from `$iterable`.
     *
     * Example:
     * ```php
     * Arr::dropLast([1, 1, 2], 1); // [1, 1]
     * Arr::dropLast(['a' => 1], 3); // []
     * Arr::dropLast(['a' => 1, 'b' => 2], 1); // ['a' => 1]
     * ```
     *
     * @template TKey of array-key
     * @template TValue
     * @param iterable<TKey, TValue> $iterable
     * Iterable to be traversed.
     * @param int $amount
     * Amount of items to be dropped from the end. Must be >= 0.
     * @return array<TKey, TValue>
     */
    public static function dropLast(
        iterable $iterable,
        int $amount,
    ): array
    {
        if ($amount < 0) {
            throw new InvalidArgumentException("Expected: \$amount >= 0. Got: {$amount}.", [
                'iterable' => $iterable,
                'amount' => $amount,
            ]);
        }

        $array = self::from($iterable);
        $length = count($array);
        return iterator_to_array(Iter::slice($array, 0, max(0, $length - $amount)));
    }

    /**
     * Drops elements in iterable until `$condition` returns **true**.
     *
     * Example:
     * ```php
     * Arr::dropUntil([1, 2, 3, 4], fn($v) => $v >= 3); // [3, 4]
     * Arr::dropUntil(['a' => 1, 'b' => 2, 'c' => 3], fn($v, $k) => $k === 'c') // ['c' => 3]
     * ```
     *
     * @template TKey of array-key
     * @template TValue
     * @param iterable<TKey, TValue> $iterable
     * Iterable to be traversed.
     * @param Closure(TValue, TKey): bool $condition
     * User defined condition callback. The callback must return a boolean value.
     * @param bool|null $reindex
     * [Optional] Result will be re-indexed if **true**.
     * If **null**, the result will be re-indexed only if it's a list.
     * Defaults to **null**.
     * @return array<TKey, TValue>
     */
    public static function dropUntil(
        iterable $iterable,
        Closure $condition,
        ?bool $reindex = null,
    ): array
    {
        $array = self::from($iterable);
        $reindex ??= array_is_list($array);
        return iterator_to_array(Iter::dropUntil($array, $condition, $reindex));
    }

    /**
     * Drops elements in iterable while `$condition` returns **true**.
     *
     * Example:
     * ```php
     * Arr::dropWhile([1, 2, 3, 4], fn($v) => $v < 3); // [3, 4]
     * Arr::dropWhile(['b' => 2, 'c' => 3], fn($v, $k) => $v < 3); // ['c' => 3]
     * ```
     *
     * @template TKey of array-key
     * @template TValue
     * @param iterable<TKey, TValue> $iterable
     * Iterable to be traversed.
     * @param Closure(TValue, TKey): bool $condition
     * User defined condition callback. The callback must return a boolean value.
     * @param bool|null $reindex
     * [Optional] Result will be re-indexed if **true**.
     * If **null**, the result will be re-indexed only if it's a list.
     * Defaults to **null**.
     * @return array<TKey, TValue>
     */
    public static function dropWhile(
        iterable $iterable,
        Closure $condition,
        ?bool $reindex = null,
    ): array
    {
        $array = self::from($iterable);
        $reindex ??= array_is_list($array);
        return iterator_to_array(Iter::dropWhile($array, $condition, $reindex));
    }

    /**
     * Returns duplicate values in `$iterable`.
     *
     * Example:
     * ```php
     * Arr::duplicates([1, 1, 2, null, 3, null]); // [1, null]
     * Arr::duplicates(['a' => 1, 'b' => 1, 'c' => 2]); // [1]
     * ```
     *
     * @template TKey of array-key
     * @template TValue
     * @param iterable<TKey, TValue> $iterable
     * Iterable to be traversed.
     * @param bool|null $reindex
     * [Optional] Result will be re-indexed if **true**.
     * If **null**, the result will be re-indexed only if it's a list.
     * Defaults to **null**.
     * @return array<TKey, TValue>
     * An array containing duplicate values.
     */
    public static function duplicates(
        iterable $iterable,
        ?bool $reindex = null,
    ): array
    {
        $array = [];
        $refs = [];
        foreach ($iterable as $key => $val) {
            $ref = self::valueToKeyString($val);
            $refs[$ref][] = $key;
            $array[$key] = $val;
        }

        $reindex ??= array_is_list($array);
        $duplicates = [];
        foreach ($refs as $keys) {
            if (count($keys) > 1) {
                $key = $keys[0];
                $reindex
                    ? $duplicates[] = $array[$key]
                    : $duplicates[$key] = $array[$key];
            }
        }

        return $duplicates;
    }

    /**
     * Iterates through `$iterable` and invoke `$callback` for each element.
     *
     * Example:
     * ```php
     * Arr::each([1, 2], function(int $i) => {
     *     echo $i;
     * }); // echoes 12
     *
     * Arr::each(['a' => 1, 'b' => 2], function($v, $k) => {
     *     echo "$k$v";
     * }); // echoes a1b2
     * ```
     *
     * @template TKey of array-key
     * @template TValue
     * @param iterable<TKey, TValue> $iterable
     * Iterable to be traversed.
     * @param Closure(TValue, TKey): void $callback
     * Callback which is called for every element of `$iterable`.
     * @return void
     */
    public static function each(
        iterable $iterable,
        Closure $callback,
    ): void
    {
        iterator_to_array(Iter::each($iterable, $callback));
    }

    /**
     * Returns **true** if `$iterable` ends with the given `$values`, **false** otherwise.
     *
     * Example:
     * ```php
     * Arr::endsWith([1, 2, 3], [2, 3]); // true
     * Arr::endsWith([1, 2, 3], [1, 3]); // false
     * Arr::endsWith([1, 2, 3], [1, 2, 3, 4]); // false
     * ```
     *
     * @template TKey of array-key
     * @template TValue
     * @param iterable<TKey, TValue> $iterable
     * Iterable to be checked.
     * @param iterable<int, TValue> $values
     * Values to be checked against.
     * @return bool
     */
    public static function endsWith(
        iterable $iterable,
        iterable $values,
    ): bool
    {
        $array = self::values($iterable);
        $values = self::values($values);
        $sizeOfArray = count($array) - 1;
        $sizeOfValues = count($values) - 1;

        if ($sizeOfValues > $sizeOfArray) {
            return false;
        }

        for ($i = $sizeOfValues, $j = $sizeOfArray; $i >= 0; $i--, $j--) {
            if ($values[$i] !== $array[$j]) {
                return false;
            }
        }
        return true;
    }

    /**
     * Ensures that count of `$iterable` is equal to `$size`.
     * Throws `CountMismatchException` if count is not equal to `$size`.
     *
     * @template TKey of array-key
     * @template TValue
     * @param iterable<TKey, TValue> $iterable
     * Iterable to be checked.
     * @param int $size
     * Expected size of the iterable.
     * @return void
     */
    public static function ensureCountIs(
        iterable $iterable,
        int $size,
    ): void
    {
        $selfCount = self::count($iterable);
        if ($selfCount !== $size) {
            throw new CountMismatchException("Expected count: {$size}, Got: {$selfCount}.", [
                'iterable' => $iterable,
                'count' => $size,
            ]);
        }
    }

    /**
     * Ensures that all elements of `$iterable` are of the given `$type`.
     * Throws `InvalidTypeException` if `$type` is not a valid type.
     * Throws `TypeMismatchException` if any element is not of the expected type.
     * Empty `$iterable` are considered valid.
     *
     * @template TKey of array-key
     * @template TValue
     * @param iterable<TKey, TValue> $iterable
     * Iterable to be checked.
     * @param string $type
     * Type(s) to be checked against. Ex: 'int|string|null'
     * @return void
     */
    public static function ensureElementType(
        iterable $iterable,
        string $type,
    ): void
    {
        foreach ($iterable as $key => $val) {
            if (Type::is($val, $type)) {
                continue;
            }

            $given = Type::for($val);
            throw new TypeMismatchException("Expected type: {$type}, Got: {$given} at {$key}.", [
                'iterable' => $iterable,
                'type' => $type,
                'got' => $given,
            ]);
        }
    }

    /**
     * Ensures that `$iterable` only contains the given `$keys`.
     * Throws `ExcessKeyException` if `$iterable` contains more keys than `$keys`.
     * Throws `MissingKeyException` if `$iterable` contains fewer keys than `$keys`.
     *
     * @template TKey of array-key
     * @template TValue
     * @param iterable<TKey, TValue> $iterable
     * Iterable to be checked.
     * @param iterable<int, TKey> $keys
     * Keys to be checked against.
     * @return void
     */
    public static function ensureExactKeys(
        iterable $iterable,
        iterable $keys,
    ): void
    {
        $asserting = array_keys(self::from($iterable));
        $keys = self::from($keys);

        $excess = self::diff($asserting, $keys);
        if (count($excess) > 0) {
            throw new ExcessKeyException($excess, [
                'iterable' => $iterable,
                'keys' => $keys,
                'excess' => $excess,
            ]);
        }

        $missing = self::diff($keys, $asserting);
        if (count($missing) > 0) {
            throw new MissingKeyException($missing, [
                'iterable' => $iterable,
                'keys' => $keys,
                'missing' => $missing,
            ]);
        }
    }

    /**
     * Iterates over each element in iterable and passes them to the callback function.
     * If the callback function returns **true** the element is passed on to the new array.
     *
     * Example:
     * ```php
     * Arr::filter([null, '', 1], fn($v) => $v === ''); // [null, 1]
     * Arr::filter([null, '', 0], Str::isNotBlank(...)); // [0]
     * Arr::filter(['a' => true, 'b' => 1], fn($v) => $v === 1); // ['b' => 1]
     * ```
     *
     * Alias of `self::takeIf()`
     *
     * @template TKey of array-key
     * @template TValue
     * @param iterable<TKey, TValue> $iterable
     * Iterable to be traversed.
     * @param Closure(TValue, TKey): bool $condition
     * User defined condition callback. The callback must return a boolean value.
     * @param bool|null $reindex
     * [Optional] Result will be re-indexed if **true**.
     * If **null**, the result will be re-indexed only if it's a list.
     * Defaults to **null**.
     * @return array<TKey, TValue>
     */
    public static function filter(
        iterable $iterable,
        Closure $condition,
        ?bool $reindex = null,
    ): array
    {
        return self::takeIf($iterable, $condition, $reindex);
    }

    /**
     * Returns the first element in `$iterable`.
     * If `$condition` is set, the first element which meets the condition is returned instead.
     * Throws `NoMatchFoundException` if no condition is met.
     * Throws `EmptyNotAllowedException` if `$iterable` is empty.
     *
     * Example:
     * ```php
     * Arr::first([1, 2], fn($val) => $val > 1); // 2
     * Arr::first([1, 2], fn($val) => $val > 2); // NoMatchFoundException: Failed to find matching condition.
     * Arr::first([], fn($val) => $val > 2); // EmptyNotAllowedException: $iterable must contain at least one element.
     * ```
     *
     * @template TKey of array-key
     * @template TValue
     * @param iterable<TKey, TValue> $iterable
     * Iterable to be traversed.
     * @param Closure(TValue, TKey): bool|null $condition
     * [Optional] User defined condition callback. The callback must return a boolean value.
     * Defaults to **null**.
     * @return TValue
     */
    public static function first(
        iterable $iterable,
        ?Closure $condition = null,
    ): mixed
    {
        $result = self::firstOr($iterable, self::miss(), $condition);

        if ($result instanceof self) {
            $exception = ($condition !== null)
                ? new NoMatchFoundException('Failed to find matching condition.')
                : new EmptyNotAllowedException('$iterable must contain at least one element.');
            throw $exception->setContext([
                'iterable' => $iterable,
                'condition' => $condition,
            ]);
        }

        return $result;
    }

    /**
     * Returns the first index of `$iterable` which meets the given `$condition`.
     * Throws `NoMatchFoundException` if no condition is met.
     *
     * Example:
     * ```php
     * Arr::firstIndex([1, 2, 3], fn($val) => $val > 1); // 1
     * Arr::firstIndex([1, 2, 3], fn($val) => $val > 3); // null
     * Arr::firstIndex(['a' => 1, 'b' => 2], fn($val, $key) => $key === 'b'); // 1
     * Arr::firstIndex([1], fn($v, $k) => false); // NoMatchFoundException
     * ```
     *
     * @template TKey of array-key
     * @template TValue
     * @param iterable<TKey, TValue> $iterable
     * Iterable to be traversed.
     * @param Closure(TValue, TKey): bool|TValue $condition
     * User defined condition callback. The callback must return a boolean value.
     * @return int
     */
    public static function firstIndex(
        iterable $iterable,
        mixed $condition,
    ): int
    {
        $result = self::firstIndexOrNull($iterable, $condition);

        if ($result === null) {
            throw new NoMatchFoundException('Failed to find matching condition.', [
                'iterable' => $iterable,
                'condition' => $condition,
            ]);
        }

        return $result;
    }

    /**
     * Returns the first index of `$iterable` which meets the given `$condition`.
     * Returns **null** if there were no matches.
     *
     * Example:
     * ```php
     * Arr::firstIndexOrNull([1, 2, 3], fn($val) => $val > 1); // 1
     * Arr::firstIndexOrNull([1, 2, 3], fn($val) => $val > 3); // null
     * Arr::firstIndexOrNull(['a' => 1, 'b' => 2], fn($val, $key) => $key === 'b'); // 1
     * Arr::firstIndexOrNull([1], fn($v, $k) => false); // null
     * ```
     *
     * @template TKey of array-key
     * @template TValue
     * @param iterable<TKey, TValue> $iterable
     * Iterable to be traversed.
     * @param Closure(TValue, TKey): bool|TValue $condition
     * User defined condition callback. The callback must return a boolean value.
     * @return int|null
     */
    public static function firstIndexOrNull(
        iterable $iterable,
        mixed $condition,
    ): ?int
    {
        if (!($condition instanceof Closure)) {
            $condition = Func::same($condition);
        }

        $count = 0;
        foreach ($iterable as $key => $val) {
            if (self::verifyBool($condition, $key, $val)) {
                return $count;
            }
            ++$count;
        }
        return null;
    }

    /**
     * Returns the first key of `$iterable` which meets the given `$condition`.
     * Throws `NoMatchFoundException` if no condition is met.
     * Throws `EmptyNotAllowedException` if `$iterable` is empty.
     *
     * Example:
     * ```php
     * Arr::firstKey(['a' => 1, 'b' => 2], fn($v, $k) => $k === 'b'); // 'b'
     * Arr::firstKey([1, 2, 3], fn($val) => $val > 1); // 1
     * Arr::firstKey([1, 2, 3], fn($val) => $val > 3); // NoMatchFoundException
     * ```
     *
     * @template TKey of array-key
     * @template TValue
     * @param iterable<TKey, TValue> $iterable
     * Iterable to be traversed.
     * @param Closure(TValue, TKey): bool|null $condition
     * [Optional] User defined condition callback. The callback must return a boolean value.
     * Defaults to **null**.
     * @return TKey
     */
    public static function firstKey(
        iterable $iterable,
        ?Closure $condition = null,
    ): int|string
    {
        $result = self::firstKeyOrNull($iterable, $condition);

        if ($result === null) {
            $exception = ($condition !== null)
                ? new NoMatchFoundException('Failed to find matching condition.')
                : new EmptyNotAllowedException('$iterable must contain at least one element.');
            throw $exception->setContext([
                'iterable' => $iterable,
                'condition' => $condition,
            ]);
        }

        /** @var TKey */
        return $result;
    }

    /**
     * Returns the first key of `$iterable` which meets the given `$condition`.
     * Returns **null** if `$iterable` is empty or if there were no matching conditions.
     *
     * Example:
     * ```php
     * Arr::firstKey(['a' => 1, 'b' => 2], fn($v, $k) => $k === 'b'); // 'b'
     * Arr::firstKey([1, 2, 3], fn($val) => $val > 1); // 1
     * Arr::firstKey([1, 2, 3], fn($val) => $val > 3); // null
     * ```
     *
     * @template TKey of array-key
     * @template TValue
     * @param iterable<TKey, TValue> $iterable
     * Iterable to be traversed.
     * @param Closure(TValue, TKey): bool|null $condition
     * [Optional] User defined condition callback. The callback must return a boolean value.
     * Defaults to **null**.
     * @return TKey|null
     */
    public static function firstKeyOrNull(
        iterable $iterable,
        ?Closure $condition = null,
    ): int|string|null
    {
        $condition ??= Func::true();
        foreach ($iterable as $key => $val) {
            if (self::verifyBool($condition, $key, $val)) {
                return $key;
            }
        }
        return null;
    }

    /**
     * Returns the first element in `$iterable`.
     * If `$condition` is set, the first element which meets the condition is returned instead.
     * If condition has no matches, value of `$default` is returned.
     *
     * Example:
     * ```php
     * Arr::firstOr([1, 2], 0, fn($val) => $val > 1); // 2
     * Arr::firstOr([1, 2], -1, fn($val) => $val > 2); // -1
     * ```
     *
     * @template TKey of array-key
     * @template TValue
     * @template TDefault
     * @param iterable<TKey, TValue> $iterable
     * Iterable to be traversed.
     * @param TDefault $default
     * Value that is used when the given `$condition` has no match.
     * @param Closure(TValue, TKey): bool|null $condition
     * [Optional] User defined condition callback. The callback must return a boolean value.
     * Defaults to **null**.
     * @return TValue|TDefault
     */
    public static function firstOr(
        iterable $iterable,
        mixed $default,
        ?Closure $condition = null,
    ): mixed
    {
        $condition ??= Func::true();

        foreach ($iterable as $key => $val) {
            if (self::verifyBool($condition, $key, $val)) {
                return $val;
            }
        }

        return $default;
    }

    /**
     * Returns the first element in `$iterable`.
     * If `$condition` is set, the first element which meets the condition is returned instead.
     * **null** is returned, if no element matches the `$condition` or is empty.
     *
     * Example:
     * ```php
     * Arr::firstOrNull([1, 2]); // 1
     * Arr::firstOrNull(['a' => 10, 'b' => 20]); // 10
     * Arr::firstOrNull([1, 2, 3], fn($v) => $v > 1); // 2
     * Arr::firstOrNull([1, 2, 3], fn($v) => $v > 3); // null
     * ```
     *
     * @template TKey of array-key
     * @template TValue
     * @param iterable<TKey, TValue> $iterable
     * Iterable to be traversed.
     * @param Closure(TValue, TKey): bool|null $condition
     * [Optional] User defined condition callback. The callback must return a boolean value.
     * Defaults to **null**.
     * @return TValue|null
     */
    public static function firstOrNull(
        iterable $iterable,
        ?Closure $condition = null,
    ): mixed
    {
        return self::firstOr($iterable, null, $condition);
    }

    /**
     * Applies the `$callback` to every element in the array, and flatten the results.
     *
     * Example:
     * ```php
     * Arr::flatMap([1, 2], fn($i) => [$i, -$i]); // [1, -1, 2, -2]
     * Arr::flatMap([['a' => 1], [2]], fn($a) => $a); // [1, 2]
     * ```
     *
     * @template TKey of array-key
     * @template TValue
     * @template TMapValue
     * @param iterable<TKey, TValue> $iterable
     * Iterable to be traversed.
     * @param Closure(TValue, TKey): iterable<int, TMapValue> $callback
     * Callback to be used to map the values.
     * @return list<TMapValue>
     */
    public static function flatMap(
        iterable $iterable,
        Closure $callback,
    ): array
    {
        return Arr::values(Iter::flatMap($iterable, $callback));
    }

    /**
     * Collapse `$iterable` up to the given `$depth` and turn it into a
     * single dimensional array.
     *
     * @template TKey of array-key
     * @param iterable<TKey, mixed> $iterable
     * Iterable to be traversed.
     * @param int $depth
     * [Optional] Specify how deep a nested iterable should be flattened.
     * Depth must be >= 1. Default: 1.
     * @return list<mixed>
     */
    public static function flatten(iterable $iterable, int $depth = 1): array
    {
        $result = [];
        foreach (Iter::flatten($iterable, $depth) as $val) {
            $result[] = $val;
        }
        return $result;
    }

    /**
     * Flip `$iterable` so that keys become values and values become keys.
     * Throws `InvalidKeyException` if elements contain types other than int|string.
     * Throws `DuplicateKeyException` if there are two values with the same value.
     * Set `$overwrite` to **true** to suppress this error.
     *
     * Example:
     * ```php
     * Arr::flip(['a' => 'b', 'c' => 'd']); // ['b' => 'a', 'd' => 'c']
     * Arr::flip([1, 2]); // [1 => 0, 2 => 1]
     * ```
     *
     * @template TKey of array-key
     * @template TValue of array-key
     * @param iterable<TKey, TValue> $iterable
     * Iterable to be traversed.
     * @param bool $overwrite
     * [Optional] Will overwrite existing keys if set to **true**.
     * If set to **false** and a duplicate key is found, a DuplicateKeyException will be thrown.
     * @return array<TValue, TKey>
     * The flipped array.
     */
    public static function flip(
        iterable $iterable,
        bool $overwrite = false,
    ): array
    {
        $flipped = [];
        foreach ($iterable as $key => $val) {
            if (!is_int($val) && !is_string($val)) {
                throw new InvalidKeyException('Expected: array value of type int|string. Got: ' . gettype($val) . '.', [
                    'iterable' => $iterable,
                    'key' => $key,
                    'value' => $val,
                ]);
            }

            if (!$overwrite && array_key_exists($val, $flipped)) {
                throw new DuplicateKeyException("Tried to overwrite existing key: {$val}.", [
                    'iterable' => $iterable,
                    'key' => $val,
                ]);
            }

            $flipped[$val] = $key;
        }
        return $flipped;
    }

    /**
     * Take all the values in `$iterable` and fold it into a single value.
     *
     * Example:
     * ```php
     * Arr::fold([1, 2], 10, fn(int $fold, int $val, $key) => $fold + $val); // 13
     * Arr::fold([], 10, fn() => 1); // 10
     * ```
     *
     * @template TKey of array-key
     * @template TValue
     * @template U
     * @param iterable<TKey, TValue> $iterable
     * Iterable to be traversed.
     * @param U $initial
     * The initial value passed to the first Closure as result.
     * @param Closure(U, TValue, TKey): U $callback
     * Callback which is called for every key-value pair in iterable.
     * The callback arguments are `(mixed $result, mixed $value, mixed $key)`.
     * The returned value would be used as $result for the subsequent call.
     * @return U
     */
    public static function fold(
        iterable $iterable,
        mixed $initial,
        Closure $callback,
    ): mixed
    {
        $result = $initial;
        foreach ($iterable as $key => $val) {
            $result = $callback($result, $val, $key);
        }
        return $result;
    }

    /**
     * Converts iterable to array.
     *
     * Example:
     * ```php
     * Arr::from([1, 2]); // [1, 2]
     * Arr::from((function () {
     *   yield 1;
     *   yield 2;
     * })()); // 1, 2
     * ```
     *
     * @template TKey of array-key
     * @template TValue
     * @param iterable<TKey, TValue> $iterable
     * Iterable to be traversed.
     * @return array<TKey, TValue>
     */
    public static function from(
        iterable $iterable,
    ): array
    {
        return iterator_to_array($iterable);
    }

    /**
     * Returns the element of the given key.
     * Throws `InvalidKeyException` if key does not exist.
     *
     * Example:
     * ```php
     * Arr::get([1, 2], key: 1); // 2
     * Arr::get(['a' => 1], key: 'a'); // 1
     * Arr::get(['a' => 1], key: 'c'); // InvalidKeyException: Undefined array key "c"
     * ```
     *
     * @template TKey of array-key
     * @template TValue
     * @param iterable<TKey, TValue> $iterable
     * Iterable to be traversed.
     * @param array-key $key
     * Key to look for.
     * @return TValue
     */
    public static function get(
        iterable $iterable,
        int|string $key,
    )
    {
        $result = self::getOr($iterable, $key, self::miss());

        if ($result instanceof self) {
            $formattedKey = is_string($key) ? "\"$key\"" : "$key";
            throw new InvalidKeyException("Key: {$formattedKey} does not exist.", [
                'iterable' => $iterable,
                'key' => $key,
            ]);
        }

        return $result;
    }

    /**
     * Returns the element of the given key if it exists, `$default` is returned otherwise.
     *
     * Example:
     * ```php
     * Arr::getOr(['a' => 1, 'b' => 2], key: 'a', default: 9); // 1
     * Arr::getOr(['a' => 1, 'b' => 2], key: 'c', default: 9); // 9
     * Arr::getOr([1, 2], key: 0, default: 9); // 1
     * ```
     *
     * @template TKey of array-key
     * @template TValue
     * @template TDefault
     * @param iterable<TKey, TValue> $iterable
     * Iterable to be traversed.
     * @param array-key $key
     * Key to look for.
     * @param TDefault $default
     * Default value to return if key is not found.
     * @return TValue|TDefault
     */
    public static function getOr(
        iterable $iterable,
        int|string $key,
        mixed $default,
    ): mixed
    {
        $array = self::from($iterable);
        if (array_key_exists($key, $array)) {
            return $array[$key];
        }
        return $default;
    }

    /**
     * Returns the element of the given key if it exists, `null` otherwise.
     *
     * Example:
     * ```php
     * Arr::getOrNull([1, 2], 0); // 1
     * Arr::getOrNull(['a' => 1], 'a'); // 1
     * Arr::getOrNull([], 1); // null
     * Arr::getOrNull(['a' => 1], 'b'); // null
     * ```
     *
     * @template TKey of array-key
     * @template TValue
     * @param iterable<TKey, TValue> $iterable
     * Iterable to be traversed.
     * @param array-key $key
     * Key to look for.
     * @return TValue|null
     */
    public static function getOrNull(
        iterable $iterable,
        int|string $key,
    ): mixed
    {
        return self::getOr($iterable, $key, null);
    }

    /**
     * Groups the elements of the given `$iterable` according to the string
     * returned by `$callback`.
     *
     * ```php
     * Arr::groupBy([1, 2, 3, 4], fn($n) => $n % 3); // [1 => [1, 4], 2 => [2], 0 => [3]]
     * Arr::groupBy([65, 66, 65], fn($n) => chr($n)); // ['A' => [65, 65], 'B' => [66]]
     * ```
     *
     * @template TGroupKey of array-key
     * @template TKey of array-key
     * @template TValue
     * @param iterable<TKey, TValue> $iterable
     * Iterable to be traversed.
     * @param Closure(TValue, TKey): TGroupKey $callback
     * Callback to determine the group of the element.
     * @param bool|null $reindex
     * [Optional] Result will be re-indexed if **true**.
     * If **null**, the result will be re-indexed only if it's a list.
     * Defaults to **null**.
     * @return array<TGroupKey, array<int|TKey, TValue>>
     */
    public static function groupBy(
        iterable $iterable,
        Closure $callback,
        ?bool $reindex = null,
    ): array
    {
        $array = self::from($iterable);
        $reindex ??= array_is_list($array);

        $map = [];
        foreach ($iterable as $key => $val) {
            $groupKey = $callback($val, $key);
            if (!is_int($groupKey) && !is_string($groupKey)) {
                $type = gettype($groupKey);
                throw new InvalidKeyException("Expected: Grouping key of type int|string. Got: {$type}.", [
                    'iterable' => $iterable,
                    'callback' => $callback,
                    'key' => $key,
                    'value' => $val,
                    'groupKey' => $groupKey,
                ]);
            }
            $map[$groupKey] ??= [];
            $reindex
                ? $map[$groupKey][] = $val
                : $map[$groupKey][$key] = $val;
        }

        return $map;
    }

    /**
     * Takes an `$array` (reference) and insert `$values` at the given `$index`.
     *
     * Throws `DuplicateKeyException` when the keys in `$values` already exist in `$array`.
     * Change the `overwrite` argument to **true** to suppress this error.
     *
     * Example:
     * ```php
     * $list = [1, 3];
     * Arr::insertAt($list, 1, [2]); // [1, 2, 3]
     *
     * $map = ['a' => 1, 'c' => 2];
     * Arr::insertAt($map, 1, ['b' => 1]); // ['a' => 1, 'b' => 1 'c' => 2]
     * ```
     *
     * @template TKey of array-key
     * @template TValue
     * @param array<TKey, TValue> $array
     * [Reference] Array to be inserted.
     * @param int $index
     * The position where the values will be inserted.
     * @param iterable<TKey, TValue> $values
     * One or more values that will be inserted.
     * @param bool|null $reindex
     * [Optional] Result will be re-indexed if **true**.
     * If **null**, the result will be re-indexed only if it's a list.
     * Defaults to **null**.
     * @param bool $overwrite
     * [Optional] If **true**, duplicates will be overwritten for string keys.
     * If **false**, exception will be thrown on duplicate key.
     * Defaults to **false**.
     * @return void
     */
    public static function insertAt(
        array &$array,
        int $index,
        iterable $values,
        ?bool $reindex = null,
        bool $overwrite = false,
    ): void
    {
        // NOTE: This used to be simply array_splice($array, $index, 0, $value) but passing replacement
        // in the 4th argument does not preserve keys so implementation was changed to the current one.
        $values = self::from($values);

        // Offset is off by one for negative indexes (Ex: -2 inserts at 3rd element from right).
        // So we add one to correct offset. If adding to one results in 0, we set it to max count
        // to put it at the end.
        if ($index < 0) {
            $index = $index === -1 ? count($array) : $index + 1;
        }

        $reindex = $reindex ?? array_is_list($array);

        if (self::isDifferentArrayType($array, $values)) {
            $arrayType = self::getArrayType($array);
            $valuesType = self::getArrayType($values);
            $message = "\$values' array type ({$valuesType}) does not match \$array's ({$arrayType}).";
            throw new TypeMismatchException($message, [
                'array' => $array,
                'index' => $index,
                'values' => $values,
                'overwrite' => $overwrite,
            ]);
        }

        // If array is associative and overwrite is not allowed, check for duplicates before applying.
        if (!$reindex) {
            $duplicates = self::keys(self::intersectKeys($array, $values));
            if (self::isNotEmpty($duplicates)) {
                if (!$overwrite) {
                    throw new DuplicateKeyException("Tried to overwrite existing key: {$duplicates[0]}.", [
                        'array' => $array,
                        'index' => $index,
                        'values' => $values,
                        'overwrite' => $overwrite,
                        'key' => $duplicates[0],
                    ]);
                }
                foreach ($duplicates as $key) {
                    unset($array[$key]);
                }
            }
        }

        $tail = array_splice($array, $index);

        foreach ([$values, $tail] as $inserting) {
            foreach ($inserting as $key => $val) {
                $reindex
                    ? $array[] = $val
                    : $array[$key] = $val;
            }
        }
    }

    /**
     * Returns the intersection of given iterable's values.
     *
     * Example:
     * ```php
     * Arr::intersect([1, 2, 3], [2, 3, 4]); // [2, 3]
     * Arr::intersect(['a' => 1, 'b' => 2, 'c' => 3], ['b' => 1]); // ['a' => 1]
     * ```
     *
     * @template TKey of array-key
     * @template TValue
     * @param iterable<TKey, TValue> $iterable1
     * Iterable to be traversed.
     * @param iterable<TKey, TValue> $iterable2
     * Iterable to be intersected.
     * @param bool|null $reindex
     * [Optional] Result will be re-indexed if **true**.
     * If **null**, the result will be re-indexed only if it's a list.
     * Defaults to **null**.
     * @return array<TKey, TValue>
     */
    public static function intersect(
        iterable $iterable1,
        iterable $iterable2,
        ?bool $reindex = null,
    ): array
    {
        $array1 = self::from($iterable1);
        $array2 = self::from($iterable2);

        if (self::isDifferentArrayType($array1, $array2)) {
            $array1Type = self::getArrayType($array1);
            $array2Type = self::getArrayType($array2);
            $message = "\$iterable1's inner type ({$array1Type}) does not match \$iterable2's ({$array2Type}).";
            throw new TypeMismatchException($message, [
                'iterable1' => $iterable1,
                'iterable2' => $iterable2,
            ]);
        }

        $reindex ??= array_is_list($array1);

        $result = array_intersect($array1, $array2);

        return $reindex
            ? array_values($result)
            : $result;
    }

    /**
     * Returns the intersection of `$iterables` using keys for comparison.
     *
     * Example:
     * ```php
     * Arr::intersectKeys(['a' => 1, 'b' => 2, 'c' => 3], ['b' => 1]); // ['b' => 2]
     * Arr::intersectKeys([1, 2, 3], [1, 3]); // [1, 2]
     * ```
     *
     * @template TKey of array-key
     * @template TValue
     * @param iterable<TKey, TValue> $iterable1
     * Iterable to be traversed.
     * @param iterable<TKey, TValue> $iterable2
     * Iterable to be intersected.
     * @return array<TKey, TValue>
     */
    public static function intersectKeys(
        iterable $iterable1,
        iterable $iterable2,
    ): array
    {
        $array1 = self::from($iterable1);
        $array2 = self::from($iterable2);

        if (self::isDifferentArrayType($array1, $array2)) {
            $array1Type = self::getArrayType($array1);
            $array2Type = self::getArrayType($array2);
            $message = "\$iterable1's array type ({$array1Type}) does not match \$iterable2's ({$array2Type}).";
            throw new TypeMismatchException($message, [
                'iterable1' => $iterable1,
                'iterable2' => $iterable2,
            ]);
        }

        return array_intersect_key($array1, $array2);
    }

    /**
     * Returns **true** if iterable is empty, **false** otherwise.
     *
     * Example:
     * ```php
     * Arr::isEmpty([1, 2]); // false
     * Arr::isEmpty([]); // true
     * ```
     *
     * @param iterable<array-key, mixed> $iterable
     * Iterable to be traversed.
     * @return bool
     */
    public static function isEmpty(
        iterable $iterable,
    ): bool
    {
        /** @noinspection PhpLoopNeverIteratesInspection */
        foreach ($iterable as $ignored) {
            return false;
        }
        return true;
    }

    /**
     * Returns **true** if iterable is a list or empty, **false** if it's a map.
     *
     * Example:
     * ```php
     * Arr::isList([1, 2]); // true
     * Arr::isList(['a' => 1, 'b' => 2]); // false
     * Arr::isList([]); // true
     * ```
     *
     * @param iterable<array-key, mixed> $iterable
     * Iterable to be traversed.
     * @return bool
     */
    public static function isList(
        iterable $iterable,
    ): bool
    {
        return array_is_list(self::from($iterable));
    }

    /**
     * Returns **true** if iterable is a map or empty, **false** if it's a list.
     *
     * Example:
     * ```php
     * Arr::isMap([1, 2]); // false
     * Arr::isMap(['a' => 1, 'b' => 2]); // true
     * Arr::isMap([]); // true
     * ```
     *
     * @template TKey of array-key
     * @template TValue
     * @param iterable<TKey, TValue> $iterable
     * Iterable to be traversed.
     * @return bool
     */
    public static function isMap(
        iterable $iterable,
    ): bool
    {
        if (self::isEmpty($iterable)) {
            return true;
        }
        return !self::isList($iterable);
    }

    /**
     * Returns **true** if iterable is not empty, **false** otherwise.
     *
     * Example:
     * ```php
     * Arr::isNotEmpty([1, 2]); // true
     * Arr::isNotEmpty([]); // false
     * ```
     *
     * @param iterable<array-key, mixed> $iterable
     * Iterable to be traversed.
     * @return bool
     */
    public static function isNotEmpty(
        iterable $iterable,
    ): bool
    {
        return !self::isEmpty($iterable);
    }

    /**
     * Concatenates all the elements in `$iterable` into a single
     * string using the provided `$glue`. Optional prefix and suffix can
     * also be added to the result string.
     *
     * Example:
     * ```php
     * Arr::join([1, 2], ', '); // "1, 2"
     * Arr::join([1, 2], ', ', '[', ']'); // "[1, 2]"
     * ```
     *
     * @param iterable<array-key, mixed> $iterable
     * Iterable to be traversed.
     * @param string $glue
     * String used to join the elements.
     * @param string|null $prefix
     * [Optional] Prefix added to the joined string.
     * @param string|null $suffix
     * [Optional] Suffix added to the joined string.
     * @return string
     */
    public static function join(
        iterable $iterable,
        string $glue,
        ?string $prefix = null,
        ?string $suffix = null,
    ): string
    {
        $str = null;
        foreach ($iterable as $value) {
            $str .= $str !== null
                ? $glue . $value
                : $value;
        }
        return $prefix . $str . $suffix;
    }

    /**
     * Returns the key at the given index.
     * Throws `IndexOutOfBoundsException` if the index does not exist.
     *
     * Example:
     * ```php
     * Arr::keyAt(['a' => 1, 'b' => 2], 1); // "b"
     * Arr::keyAt(['a' => 1, 'b' => 2], 2); // throws IndexOutOfBoundsException
     * Arr::keyAt(['a' => 1, 'b' => 2], -2); // "a"
     * ```
     *
     * @template TKey of array-key
     * @template TValue
     * @param iterable<TKey, TValue> $iterable
     * @param int $index
     * @return int|string
     */
    public static function keyAt(
        iterable $iterable,
        int $index,
    ): int|string
    {
        $result = self::keyAtOrNull($iterable, $index);

        if ($result === null) {
            throw new IndexOutOfBoundsException("\$iterable did not contain the given index: {$index}.", [
                'iterable' => $iterable,
                'index' => $index,
            ]);
        }

        return $result;
    }

    /**
     * Returns the key at the given index.
     * Returns **null** if the index does not exist.
     *
     * Example:
     * ```php
     * Arr::keyAtOrNull(['a' => 1, 'b' => 2], 1); // "b"
     * Arr::keyAtOrNull(['a' => 1, 'b' => 2], 2); // null
     * Arr::keyAtOrNull(['a' => 1, 'b' => 2], -2); // "a"
     * ```
     *
     * @template TKey of array-key
     * @template TValue
     * @param iterable<TKey, TValue> $iterable
     * @param int $index
     * @return int|string|null
     */
    public static function keyAtOrNull(
        iterable $iterable,
        int $index,
    ): int|string|null
    {
        if (is_array($iterable) && $index >= count($iterable)) {
            return null;
        }

        if ($index < 0) {
            $iterable = Arr::from($iterable);
            $index += count($iterable);
        }

        $count = 0;
        foreach ($iterable as $key => $ignored) {
            if ($count === $index) {
                return $key;
            }
            $count++;
        }
        return null;
    }

    /**
     * Returns an array which contains values from `$iterable` with the keys
     * being the results of running `$callback($val, $key)` on each element.
     *
     * Throws `DuplicateKeyException` when the value returned by `$callback`
     * already exist in `$array` as a key. Set `$overwrite` to **true** to
     * suppress this error.
     *
     * Example:
     * ```php
     * Arr::keyBy([1, 2], fn($v, $k) => "a{$k}"); // ['a0' => 1, 'a1' => 2]
     * ```
     *
     * @template TNewKey of array-key
     * @template TKey of array-key
     * @template TValue
     * @param iterable<TKey, TValue> $iterable
     * Iterable to be traversed.
     * @param Closure(TValue, TKey): TNewKey $callback
     * Callback which returns the key for the new map.
     * @param bool $overwrite
     * [Optional] If **true**, duplicate keys will be overwritten.
     * If **false**, exception will be thrown on duplicate keys.
     * @return array<TNewKey, TValue>
     */
    public static function keyBy(
        iterable $iterable,
        Closure $callback,
        bool $overwrite = false,
    ): array
    {
        $result = [];
        foreach ($iterable as $oldKey => $val) {
            $newKey = self::ensureKeyType($callback($val, $oldKey));

            if (!$overwrite && array_key_exists($newKey, $result)) {
                throw new DuplicateKeyException("Tried to overwrite existing key: {$newKey}.", [
                    'iterable' => $iterable,
                    'newKey' => $newKey,
                ]);
            }

            $result[$newKey] = $val;
        }
        return $result;
    }

    /**
     * Returns all the keys of `$iterable` as an array.
     *
     * Example:
     * ```php
     * Arr::keys([1, 2]); // [0, 1]
     * Arr::keys(['a' => 1, 'b' => 2]); // ['a', 'b']
     * ```
     *
     * @template TKey of array-key
     * @param iterable<TKey, mixed> $iterable
     * Iterable to be traversed.
     * @return list<TKey>
     */
    public static function keys(
        iterable $iterable,
    ): array
    {
        return Arr::values(Iter::keys($iterable));
    }

    /**
     * Returns the last element in `$iterable`.
     * If `$condition` is set, the last element which meets the condition is returned instead.
     * Throws `NoMatchFoundException` if no condition is met.
     * Throws `EmptyNotAllowedException` if `$iterable` is empty.
     *
     * Example:
     * ```php
     * Arr::last([1, 2], fn($val) => true); // 2
     * Arr::last([1, 2], fn($val) => false); // NoMatchFoundException: Failed to find matching condition.
     * Arr::last([], fn($val) => true); // EmptyNotAllowedException: $iterable must contain at least one element.
     * ```
     *
     * @template TKey of array-key
     * @template TValue
     * @param iterable<TKey, TValue> $iterable
     * Iterable to be traversed.
     * @param Closure(TValue, TKey): bool|null $condition
     * [Optional] User defined condition callback. The callback must return a boolean value.
     * Defaults to **null**.
     * @return TValue
     */
    public static function last(
        iterable $iterable,
        ?Closure $condition = null,
    ): mixed
    {
        $result = self::lastOr($iterable, self::miss(), $condition);

        if ($result instanceof self) {
            $exception = ($condition !== null)
                ? new NoMatchFoundException('Failed to find matching condition.')
                : new EmptyNotAllowedException('$iterable must contain at least one element.');
            throw $exception->setContext([
                'iterable' => $iterable,
                'condition' => $condition,
            ]);
        }

        return $result;
    }

    /**
     * Returns the last index of `$iterable` which meets the given `$condition`.
     * Throws `NoMatchFoundException` if no condition is met.
     * Throws `EmptyNotAllowedException` if `$iterable` is empty.
     *
     * Example:
     * ```php
     * Arr::lastIndex([1, 2, 3, 4], fn($v) => true); // 3
     * Arr::lastIndex(['a' => 1, 'b' => 2]); // 1
     * Arr::lastIndex([1, 2], fn($v) => false); // NoMatchFoundException
     * ```
     *
     * @template TKey of array-key
     * @template TValue
     * @param iterable<TKey, TValue> $iterable
     * Iterable to be traversed.
     * @param Closure(TValue, TKey): bool|null $condition
     * [Optional] User defined condition callback. The callback must return a boolean value.
     * Defaults to **null**.
     * @return int
     */
    public static function lastIndex(
        iterable $iterable,
        ?Closure $condition = null,
    ): int
    {
        $result = self::lastIndexOrNull($iterable, $condition);

        if ($result === null) {
            $exception = ($condition !== null)
                ? new NoMatchFoundException('Failed to find matching condition.')
                : new EmptyNotAllowedException('$iterable must contain at least one element.');
            throw $exception->setContext([
                'iterable' => $iterable,
                'condition' => $condition,
            ]);
        }

        return $result;
    }

    /**
     * Returns the last index of `$iterable` which meets the given `$condition`.
     * Returns **null** if there were no matches.
     *
     * Example:
     * ```php
     * Arr::lastIndexOrNull([1, 2, 3, 4], fn($v) => true); // 3
     * Arr::lastIndexOrNull(['a' => 1, 'b' => 2]); // 1
     * Arr::lastIndexOrNull([1, 2], fn($v) => false); // null
     * ```
     *
     * @template TKey of array-key
     * @template TValue
     * @param iterable<TKey, TValue> $iterable
     * Iterable to be traversed.
     * @param Closure(TValue, TKey): bool|null $condition
     * [Optional] User defined condition callback. The callback must return a boolean value.
     * Defaults to **null**.
     * @return int|null
     */
    public static function lastIndexOrNull(
        iterable $iterable,
        ?Closure $condition = null,
    ): ?int
    {
        $array = self::from($iterable);

        $count = count($array);

        if ($count > 0) {
            if ($condition === null) {
                return $count - 1;
            }
            end($array);
            while (($key = key($array)) !== null) {
                --$count;
                $val = current($array);
                /** @var TKey $key */
                /** @var TValue $val */
                if (self::verifyBool($condition, $key, $val)) {
                    return $count;
                }
                prev($array);
            }
        }

        return null;
    }

    /**
     * Returns the last key of `$iterable` which meets the `$condition`.
     * Throws `NoMatchFoundException` if no condition is met.
     * Throws `EmptyNotAllowedException` if `$iterable` is empty.
     *
     * Example:
     * ```php
     * Arr::lastKey(['a' => 1, 'b' => 2]); // 'b'
     * Arr::lastKey([1, 2], fn($val) => true); // 2
     * Arr::lastKey([1, 2], fn($val) => false); // NoMatchFoundException
     * ```
     *
     * @template TKey of array-key
     * @template TValue
     * @param iterable<TKey, TValue> $iterable
     * Iterable to be traversed.
     * @param Closure(TValue, TKey): bool|null $condition
     * [Optional] User defined condition callback. The callback must return a boolean value.
     * Defaults to **null**.
     * @return TKey
     */
    public static function lastKey(
        iterable $iterable,
        ?Closure $condition = null,
    ): mixed
    {
        $result = self::lastKeyOrNull($iterable, $condition);

        if ($result === null) {
            $exception = ($condition !== null)
                ? new NoMatchFoundException('Failed to find matching condition.')
                : new EmptyNotAllowedException('$iterable must contain at least one element.');
            throw $exception->setContext([
                'iterable' => $iterable,
                'condition' => $condition,
            ]);
        }

        /** @var TKey */
        return $result;
    }

    /**
     * Returns the last key of `$iterable` which meets the `$condition`.
     * Returns **null** if condition is not met.
     *
     * Example:
     * ```php
     * Arr::lastKeyOrNull(['a' => 1, 'b' => 2]); // 'b'
     * Arr::lastKeyOrNull([1, 2], fn($val) => true); // 2
     * Arr::lastKeyOrNull([1, 2], fn($val) => false); // null
     * ```
     *
     * @template TKey of array-key
     * @template TValue
     * @param iterable<TKey, TValue> $iterable
     * Iterable to be traversed.
     * @param Closure(TValue, TKey): bool|null $condition
     * [Optional] User defined condition callback. The callback must return a boolean value.
     * Defaults to **null**.
     * @return TKey|null
     */
    public static function lastKeyOrNull(
        iterable $iterable,
        ?Closure $condition = null,
    ): mixed
    {
        $copy = self::from($iterable);
        end($copy);

        $condition ??= Func::true();

        while (($key = key($copy)) !== null) {
            $val = current($copy);
            /** @var TKey $key */
            /** @var TValue $val */
            if (self::verifyBool($condition, $key, $val)) {
                return $key;
            }
            prev($copy);
        }

        return null;
    }

    /**
     * Returns the last element in `$iterable`.
     * If `$condition` is set, the last element which meets the condition is returned instead.
     * Returns the value of `$default` if no condition met.
     *
     * Example:
     * ```php
     * Arr::lastOr([1, 2], 0, fn($val) => true); // 2
     * Arr::lastOr([1, 2], -1, fn($val) => false); // -1
     * Arr::lastOr([], 1); // 1
     * ```
     *
     * @template TKey of array-key
     * @template TValue
     * @template TDefault
     * @param iterable<TKey, TValue> $iterable
     * Iterable to be traversed.
     * @param TDefault $default
     * Value that is used when the given `$condition` has no match.
     * @param Closure(TValue, TKey): bool|null $condition
     * [Optional] User defined condition callback. The callback must return a boolean value.
     * Defaults to **null**.
     * @return TValue|TDefault
     */
    public static function lastOr(
        iterable $iterable,
        mixed $default,
        ?Closure $condition = null,
    ): mixed
    {
        $array = self::from($iterable);
        end($array);

        $condition ??= Func::true();

        while (($key = key($array)) !== null) {
            /** @var TKey $key */
            /** @var TValue $val */
            $val = current($array);
            if (self::verifyBool($condition, $key, $val)) {
                return $val;
            }
            prev($array);
        }

        return $default;
    }

    /**
     * Returns the last element in iterable.
     * If `$condition` is set, the last element which meets the condition is returned instead.
     * Returns **null** if no element matches the `$condition` or is empty.
     *
     * Example:
     * ```php
     * Arr::lastOrNull([1, 2]); // 2
     * Arr::lastOrNull(['a' => 10, 'b' => 20]); // 20
     * Arr::lastOrNull([1, 2, 3], fn($v) => true); // 3
     * Arr::lastOrNull([1, 2, 3], fn($v) => false); // null
     * ```
     *
     * @template TKey of array-key
     * @template TValue
     * @param iterable<TKey, TValue> $iterable
     * Iterable to be traversed.
     * @param Closure(TValue, TKey): bool|null $condition
     * [Optional] User defined condition callback. The callback must return a boolean value.
     * Defaults to **null**.
     * @return TValue|null
     */
    public static function lastOrNull(
        iterable $iterable,
        ?Closure $condition = null,
    ): mixed
    {
        return self::lastOr($iterable, null, $condition);
    }

    /**
     * Returns a new array containing results returned from invoking
     * `$callback` on each element of `$iterable`.
     *
     * Example:
     * ```php
     * Arr::map([], fn($i) => true) // []
     * Arr::map(['', 'a', 'aa'], strlen(...)) // [0, 1, 2]
     * Arr::map(['a' => 1, 'b' => 2, 'c' => 3], fn($i) => $i * 2) // ['a' => 2, 'b' => 4, 'c' => 6]
     * Arr::map(['a', 'b', 'c'], fn($i, $k) => $k) // [0, 1, 2]
     * ```
     * @template TKey of array-key
     * @template TValue
     * @template TMapValue
     * @param iterable<TKey, TValue> $iterable
     * Iterable to be traversed.
     * @param Closure(TValue, TKey): TMapValue $callback
     * Callback to be used to map the values.
     * @return ($iterable is list<TValue> ? list<TMapValue> : array<TKey, TMapValue>)
     */
    public static function map(
        iterable $iterable,
        Closure $callback,
    ): array
    {
        return iterator_to_array(Iter::map($iterable, $callback));
    }

    /**
     * Applies the `$callback` to every element in the array, and flatten the results.
     *
     * Example:
     * ```php
     * Arr::mapWithKey(['a' => 1, 'b' => 2], fn($v, $k) => yield "$k$v" => $v) // ['a1' => 1, 'b2' => 2]
     * ```
     *
     * @template TKey of array-key
     * @template TValue
     * @template TMapKey of array-key
     * @template TMapValue
     * @param iterable<TKey, TValue> $iterable
     * Iterable to be traversed.
     * @param Closure(TValue, TKey): iterable<TMapKey, TMapValue> $callback
     * Callback to be used to map the values.
     * @param bool $overwrite
     * [Optional] If **true**, duplicate keys will be overwritten. Defaults to **false**.
     * If **false**, exception will be thrown on duplicate keys.
     * @return array<TMapKey, TMapValue>
     */
    public static function mapWithKey(
        iterable $iterable,
        Closure $callback,
        bool $overwrite = false,
    ): array
    {
        $result = [];
        foreach(Iter::mapWithKey($iterable, $callback) as $key => $val) {
            if (!$overwrite && array_key_exists($key, $result)) {
                throw new DuplicateKeyException("Tried to overwrite existing key: {$key}.", [
                    'iterable' => $iterable,
                    'key' => $key,
                ]);
            }
            $result[$key] = $val;
        }
        return $result;
    }

    /**
     * Returns the largest element from `$iterable`.
     * If `$by` is given, each element will be passed to the closure and the
     * largest value returned from the closure will be returned instead.
     * Throws `InvalidElementException`, If `$iterable` contains NAN.
     * Throws `EmptyNotAllowedException` if `$iterable` is empty.
     *
     * Example:
     * ```php
     * Arr::max([], fn($i) => true) // EmptyNotAllowedException
     * Arr::max([1, 2, 3]) // 3
     * Arr::max([-1, -2, -3]) // -1
     * Arr::max([-1, -2, -3], abs(...)) // 3
     * ```
     *
     * @template TKey of array-key
     * @template TValue
     * @param iterable<TKey, TValue> $iterable
     * Iterable to be traversed.
     * @param Closure(TValue, TKey): (int|float)|null $by
     * [Optional] Called for every element in `$iterable`.
     * Returned value will be used to determine the largest number.
     * Must be int or float.
     * @return TValue
     */
    public static function max(
        iterable $iterable,
        ?Closure $by = null,
    ): mixed
    {
        $maxVal = self::maxOrNull($iterable, $by);

        if ($maxVal === null) {
            throw new EmptyNotAllowedException('$iterable must contain at least one element.', [
                'iterable' => $iterable,
            ]);
        }

        return $maxVal;
    }

    /**
     * Returns the largest element from `$iterable`.
     * If `$by` is given, each element will be passed to the closure and the
     * largest value returned from the closure will be returned instead.
     * Returns **null** if `$iterable` is empty.
     * Throws `InvalidElementException` if `$iterable` contains NAN.
     *
     * Example:
     * ```php
     * Arr::maxOrNull([], fn($i) => true) // null
     * Arr::maxOrNull([1, 2, 3]) // 3
     * Arr::maxOrNull([-1, -2, -3]) // -1
     * Arr::maxOrNull([-1, -2, -3], abs(...)) // 3
     * ```
     *
     * @template TKey of array-key
     * @template TValue
     * @param iterable<TKey, TValue> $iterable
     * Iterable to be traversed.
     * @param Closure(TValue, TKey): (int|float)|null $by
     * [Optional] Called for every element in `$iterable`.
     * Returned value will be used to determine the largest number.
     * Must be int or float.
     * @return TValue|null
     */
    public static function maxOrNull(
        iterable $iterable,
        ?Closure $by = null,
    ): mixed
    {
        $by ??= static fn(mixed $val, int|string $key): mixed => $val;

        $maxResult = null;
        $maxVal = null;

        foreach ($iterable as $key => $val) {
            $result = $by($val, $key);

            if ($maxResult === null || $result > $maxResult) {
                $maxResult = $result;
                $maxVal = $val;
            }

            if (is_nan($result)) {
                throw new InvalidElementException('$iterable cannot contain NAN.', [
                    'iterable' => $iterable,
                    'result' => $result,
                    'maxResult' => $maxResult,
                    'maxVal' => $maxVal,
                ]);
            }
        }

        return $maxVal;
    }

    /**
     * Merges one or more iterables into a single array.
     *
     * If the given keys are numeric, the keys will be re-numbered with
     * an incremented number from the last number in the new array.
     *
     * If the two iterables have the same keys, the value inside the
     * iterable the comes later will overwrite the value in the key.
     *
     * This method will only merge the key value pairs of the root depth.
     *
     * Example:
     * ```php
     * // merge list
     * Arr::merge([1, 2], [3, 4]); // [1, 2, 3, 4]
     *
     * // merge assoc
     * Arr::merge(['a' => 1], ['b' => 2]); // ['a' => 1, 'b' => 2]
     *
     * // overrides key
     * Arr::merge(['a' => 1], ['a' => 2]); // ['a' => 2]
     * ```
     *
     * @template TKey of array-key
     * @template TValue
     * @param iterable<TKey, TValue> ...$iterables
     * Iterable(s) to be merged.
     * @return ($iterables is list<TValue> ? list<TValue> : array<TKey, TValue>)
     */
    public static function merge(
        iterable ...$iterables,
    ): array
    {
        $result = null;
        foreach ($iterables as $iterable) {
            if ($result === null) {
                $result = self::from($iterable);
                continue;
            }
            $result = self::mergeRecursive($result, $iterable, 1);
        }

        if ($result === null) {
            throw new InvalidArgumentException('At least one iterable must be defined.');
        }

        return $result;
    }

    /**
     * Merges one or more iterables recursively into a single array.
     * Will merge recursively up to the given depth.
     *
     * @see merge for details on how keys and values are merged.
     *
     * Example:
     * ```php
     * Arr::mergeRecursive(
     *    ['a' => 1, 'b' => 2],
     *    ['a' => ['c' => 1]]
     * ); // ['a' => ['c' => 1], 'b' => 2]
     * ```
     *
     * @template TKey of array-key
     * @template TValue
     * @param iterable<TKey, TValue> $iterable1
     * Iterable to be traversed.
     * @param iterable<TKey, TValue> $iterable2
     * Iterable to be merged.
     * @param int<1, max> $depth
     * [Optional] Defaults to INT_MAX
     * @return array<TKey, TValue>
     */
    public static function mergeRecursive(
        iterable $iterable1,
        iterable $iterable2,
        int $depth = PHP_INT_MAX,
    ): array
    {
        $merged = self::from($iterable1);
        $merging = self::from($iterable2);

        if (self::isDifferentArrayType($merged, $merging)) {
            throw new TypeMismatchException('Tried to merge list with map. Try converting the map to a list.', [
                'iterable1' => $iterable1,
                'iterable2' => $iterable2,
                'depth' => $depth,
            ]);
        }

        foreach ($merging as $key => $val) {
            if (is_int($key)) {
                $merged[] = $val;
            } elseif ($depth > 1 && array_key_exists($key, $merged) && is_iterable($merged[$key]) && is_iterable($val)) {
                $left = $merged[$key];
                $right = $val;
                /**
                 * @var iterable<array-key, mixed> $left
                 * @var iterable<array-key, mixed> $right
                 */
                $merged[$key] = self::mergeRecursive($left, $right, $depth - 1);
            } else {
                $merged[$key] = $val;
            }
        }

        /** @var array<TKey, TValue> $merged */
        return $merged;
    }

    /**
     * Returns the smallest element from the given array.
     * If `$by` is given, each element will be passed to the closure and the
     * smallest value returned from the closure will be returned instead.
     * Throws `EmptyNotAllowedException` if `$iterable` is empty.
     * Throws `InvalidElementException` if `$iterable` contains NAN.
     *
     * Example:
     * ```php
     * Arr::min([], fn($i) => true) // EmptyNotAllowedException
     * Arr::min([1, 2, 3]) // 1
     * Arr::min([-1, -2, -3]) // -3
     * Arr::min([-1, -2, -3], abs(...)) // 3
     * Arr::min([-INF, 0.0, INF]) // INF
     * ```
     *
     * @template TKey of array-key
     * @template TValue
     * @param iterable<TKey, TValue> $iterable
     * Iterable to be traversed.
     * @param Closure(TValue, TKey): (int|float)|null $by
     * [Optional] Called for every element in `$iterable`.
     * Returned value will be used to determine the smallest number.
     * Must be int or float.
     * @return TValue
     */
    public static function min(
        iterable $iterable,
        ?Closure $by = null,
    ): mixed
    {
        $minVal = self::minOrNull($iterable, $by);

        if ($minVal === null) {
            throw new EmptyNotAllowedException('$iterable must contain at least one element.', [
                'iterable' => $iterable,
                'condition' => $by,
            ]);
        }

        return $minVal;
    }

    /**
     * Returns the smallest element from `$iterable`.
     * If `$by` is given, each element will be passed to the closure and the
     * smallest value returned from the closure will be returned instead.
     * Returns **null** if `$iterable` is empty.
     * Throws `InvalidElementException` if `$iterable` contains NAN.
     *
     * Example:
     * ```php
     * Arr::minOrNull([], fn($i) => true) // null
     * Arr::minOrNull([1, 2, 3]) // 1
     * Arr::minOrNull([-1, -2, -3]) // -3
     * Arr::minOrNull([-1, -2, -3], abs(...)) // 3
     * Arr::minOrNull([-INF, 0.0, INF]) // INF
     * ```
     *
     * @template TKey of array-key
     * @template TValue
     * @param iterable<TKey, TValue> $iterable
     * Iterable to be traversed.
     * @param Closure(TValue, TKey): (int|float)|null $by
     * [Optional] Called for every element in `$iterable`.
     * Returned value will be used to determine the smallest number.
     * Must be int or float.
     * @return TValue|null
     */
    public static function minOrNull(
        iterable $iterable,
        ?Closure $by = null,
    ): mixed
    {
        $by ??= static fn(mixed $val, int|string $key): mixed => $val;

        $minResult = null;
        $minVal = null;

        foreach ($iterable as $key => $val) {
            $result = $by($val, $key);

            if ($minResult === null || $result < $minResult) {
                $minResult = $result;
                $minVal = $val;
            }

            if (is_nan($result)) {
                throw new InvalidElementException('$iterable cannot contain NAN.', [
                    'iterable' => $iterable,
                    'result' => $result,
                    'minResult' => $minResult,
                    'minVal' => $minVal,
                ]);
            }
        }

        return $minVal;
    }

    /**
     * Returns the smallest and largest element from `$iterable` as array{ min: , max: }.
     * If `$by` is given, each element will be passed to the closure and the
     * smallest and largest value returned from the closure will be returned instead.
     * Throws `EmptyNotAllowedException` if `$iterable` is empty.
     * Throws `InvalidElementException` if `$iterable` contains NAN.
     *
     * Example:
     * ```php
     * Arr::minMax([-1, 0, 1]) // ['min' => -1, 'max' => 1]
     * Arr::minMax([1]) // ['min' => 1, 'max' => 1]
     * Arr::minMax([]) // EmptyNotAllowedException
     * ```
     *
     * @template TKey of array-key
     * @template TValue
     * @param iterable<TKey, TValue> $iterable
     * Iterable to be traversed.
     * @param Closure(TValue, TKey): (int|float)|null $by
     * [Optional] Called for every element in `$iterable`.
     * Returned value will be used to determine the highest number.
     * @return array{ min: TValue, max: TValue }
     */
    public static function minMax(
        iterable $iterable,
        ?Closure $by = null,
    ): array
    {
        $result = self::minMaxOrNull($iterable, $by);
        if ($result === null) {
            throw new EmptyNotAllowedException('$iterable must contain at least one element.', [
                'iterable' => $iterable,
                'condition' => $by,
            ]);
        }
        return $result;
    }

    /**
     * Returns the smallest and largest element from the given array.
     * If `$by` is given, each element will be passed to the closure and the
     * smallest and largest value returned from the closure will be returned instead.
     * If the `$iterable` is empty, **null** will be returned.
     * Throws `InvalidElementException` if `$iterable` contains NAN.
     *
     * Example:
     * ```php
     * Arr::minMaxOrNull([-1, 0, 1]) // ['min' => -1, 'max' => 1]
     * Arr::minMaxOrNull([]) // null
     * ```
     *
     * @template TKey of array-key
     * @template TValue
     * @param iterable<TKey, TValue> $iterable
     * Iterable to be traversed.
     * @param Closure(TValue, TKey): (int|float)|null $by
     * [Optional] Called for every element in `$iterable`.
     * Returned value will be used to determine the smallest and highest number.
     * @return array{ min: TValue, max: TValue }
     */
    public static function minMaxOrNull(
        iterable $iterable,
        ?Closure $by = null,
    ): ?array
    {
        $by ??= static fn(mixed $val, int|string $key): mixed => $val;

        $minResult = null;
        $minVal = null;
        $maxResult = null;
        $maxVal = null;

        foreach ($iterable as $key => $val) {
            $result = $by($val, $key);

            if ($minResult === null || $result < $minResult) {
                $minResult = $result;
                $minVal = $val;
            }

            if ($maxResult === null || $result > $maxResult) {
                $maxResult = $result;
                $maxVal = $val;
            }

            if (is_nan($result)) {
                throw new InvalidElementException('$iterable cannot contain NAN.', [
                    'iterable' => $iterable,
                    'result' => $result,
                    'minResult' => $minResult,
                    'minVal' => $minVal,
                    'maxResult' => $maxResult,
                    'maxVal' => $maxVal,
                ]);
            }
        }

        if ($minVal === null || $maxVal === null) {
            return null;
        }

        return [
            'min' => $minVal,
            'max' => $maxVal,
        ];
    }

    /**
     * @param mixed ...$values
     * @return array<array-key, mixed>
     */
    public static function of(mixed ...$values): array
    {
        return $values;
    }

    /**
     * Returns a list (array) with a given value padded to the left side of
     * `$iterable` up to `$length`.
     *
     * Padding can only be applied to a list, so make sure to provide an iterable
     * that only contain int as key. If an iterable with a string key is given,
     * a `TypeMismatchException` will be thrown.
     *
     * Example:
     * ```php
     * Arr::padLeft(['a'], 3, 'b'); // ['b', 'b', 'a']
     * Arr::padLeft(['a' => 1], 2, 2); // TypeMismatchException
     * ```
     *
     * @template TValue
     * @param iterable<int, TValue> $iterable
     * Iterable to be traversed.
     * @param int $length
     * Apply padding until the array size reaches the given length. Must be >= 0.
     * @param TValue $value
     * Value inserted into each padding.
     * @return list<TValue>
     */
    public static function padLeft(
        iterable $iterable,
        int $length,
        mixed $value,
    ): array
    {
        $array = self::from($iterable);
        $arrSize = count($array);

        if (!array_is_list($array)) {
            throw new TypeMismatchException('Padding can only be applied to a list, map given.', [
                'iterable' => $iterable,
                'length' => $length,
                'value' => $value,
            ]);
        }

        if ($length < 0) {
            throw new InvalidArgumentException("Expected: \$length >= 0. Got: {$length}.", [
                'iterable' => $iterable,
                'length' => $length,
                'value' => $value,
            ]);
        }

        if ($arrSize <= $length) {
            $repeated = array_fill(0, $length - $arrSize, $value);
            return self::merge($repeated, $array);
        }

        return $array;
    }

    /**
     * Returns a list (array) with a given value padded to the right side of
     * `$iterable` up to `$length`.
     *
     * Padding can only be applied to a list, so make sure to provide an iterable
     * that only contain int as key. If an iterable with a string key is given,
     * a `TypeMismatchException` will be thrown.
     *
     * Example:
     * ```php
     * Arr::padRight(['a'], 3, 'b'); // ['a', 'b', 'b']
     * Arr::padRight(['a' => 1], 2, 2); // TypeMismatchException
     * ```
     *
     * @template TValue
     * @param iterable<int, TValue> $iterable
     * Iterable to be traversed.
     * @param int $length
     * Apply padding until the array size reaches the given length. Must be >= 0.
     * @param TValue $value
     * Value inserted into each padding.
     * @return list<TValue>
     */
    public static function padRight(
        iterable $iterable,
        int $length,
        mixed $value,
    ): array
    {
        $array = self::from($iterable);
        $arrSize = count($array);

        if (!array_is_list($array)) {
            throw new TypeMismatchException('Padding can only be applied to a list, map given.', [
                'iterable' => $iterable,
                'length' => $length,
                'value' => $value,
            ]);
        }

        if ($length < 0) {
            throw new InvalidArgumentException("Expected: \$length >= 0. Got: {$length}.", [
                'iterable' => $iterable,
                'length' => $length,
                'value' => $value,
            ]);
        }

        if ($arrSize <= $length) {
            $repeated = array_fill(0, $length - $arrSize, $value);
            return self::merge($array, $repeated);
        }

        return $array;
    }

    /**
     * Returns a list with two array elements.
     * All elements in `$iterable` evaluated to be **true** will be pushed to
     * the first array. Elements evaluated to be **false** will be pushed to
     * the second array.
     *
     * Example:
     * ```php
     * Arr::partition([1, 2, 3], fn($v) => (bool) ($v % 2)); // [[1, 3], [2]]
     * Arr::partition(['a' => 1, 'b' => 2], fn($v) => $v === 1); // [['a' => 1], ['b' => 2]]
     * ```
     *
     * @template TKey of array-key
     * @template TValue
     * @param iterable<TKey, TValue> $iterable
     * Iterable to be traversed.
     * @param Closure(TValue, TKey): bool $condition
     * Closure to evaluate each element.
     * @param bool|null $reindex
     * [Optional] Result will be re-indexed if **true**.
     * If **null**, the result will be re-indexed only if it's a list.
     * Defaults to **null**.
     * @return array{ array<TKey, TValue>, array<TKey, TValue> }
     */
    public static function partition(
        iterable $iterable,
        Closure $condition,
        ?bool $reindex = null,
    ): array
    {
        $array = self::from($iterable);
        $reindex ??= array_is_list($array);
        $truthy = [];
        $falsy = [];
        foreach ($array as $key => $value) {
            if (self::verifyBool($condition, $key, $value)) {
                $reindex
                    ? $truthy[] = $value
                    : $truthy[$key] = $value;
            } else {
                $reindex
                    ? $falsy[] = $value
                    : $falsy[$key] = $value;
            }
        }
        return [$truthy, $falsy];
    }

    /**
     * Pops the element off the end of the given array (reference).
     * Throws `EmptyNotAllowedException`, if `&$array` is empty.
     *
     * Example:
     * ```php
     * $array = ['a' => 1];
     * Arr::pop($array); // 1
     * Arr::pop($array); // EmptyNotAllowedException
     * ```
     *
     * @template TKey of array-key
     * @template TValue
     * @param array<TKey, TValue> $array
     * @param-out array<TKey, TValue> $array
     * [Reference] Array to be popped.
     * @return TValue
     */
    public static function pop(
        array &$array,
    ): mixed
    {
        $popped = self::popOrNull($array);

        if ($popped === null) {
            throw new EmptyNotAllowedException('&$array must contain at least one element.', [
                'array' => $array,
            ]);
        }

        return $popped;
    }

    /**
     * Pops elements off the end of the given array (reference).
     * Returns the popped elements in a new array.
     *
     * Example:
     * ```php
     * $array = [1, 2, 3];
     * Arr::popMany($array, 2); // [1] (and $array will be [2, 3])
     * Arr::popMany($array, 1); // null
     * ```
     *
     * @template TKey of array-key
     * @template TValue
     * @param array<TKey, TValue> $array
     * @param-out array<TKey, TValue> $array
     * [Reference] Array to be popped.
     * @param int $amount
     * Amount of elements to pop. Must be a positive integer.
     * @return array<TKey, TValue>
     */
    public static function popMany(
        array &$array,
        int $amount,
    ): array
    {
        if ($amount < 1) {
            throw new InvalidArgumentException("Expected: \$amount >= 1. Got: {$amount}.", [
                'array' => $array,
                'amount' => $amount,
            ]);
        }
        return array_splice($array, -$amount);
    }

    /**
     * Pops the element off the end of the given array (reference).
     * Returns **null**, if the `&$array` is empty.
     *
     * Example:
     * ```php
     * $array = ['a' => 1];
     * Arr::popOrNull($array); // 1
     * Arr::popOrNull($array); // null
     * ```
     *
     * @template TKey of array-key
     * @template TValue
     * @param array<TKey, TValue> $array
     * @param-out array<TKey, TValue> $array
     * [Reference] Array to be popped.
     * @return TValue|null
     */
    public static function popOrNull(
        array &$array,
    ): mixed
    {
        return array_pop($array);
    }

    /**
     * Prepend value(s) to the front of `$iterable`.
     * `$iterable` must be convertible to a list.
     * Throws `TypeMismatchException` if map is given.
     *
     * Example:
     * ```php
     * Arr::prepend([1, 2], 0); // $array will be [0, 1, 2]
     * Arr::prepend([1, 2], 3, 4); // $array will be [1, 2, 3, 4]
     * ```
     *
     * @template T
     * @param iterable<array-key, T> $iterable
     * Iterable to be prepended.
     * @param T ...$values
     * Value(s) to be prepended to the array.
     * @return list<T>
     */
    public static function prepend(
        iterable $iterable,
        mixed ...$values,
    ): array
    {
        $array = self::from($iterable);
        if (!array_is_list($array)) {
            throw new TypeMismatchException('$array must be a list, map given.', [
                'iterable' => $iterable,
                'values' => $values,
            ]);
        }
        if (!array_is_list($values)) {
            $values = array_values($values);
        }
        array_unshift($array, ...$values);
        return $array;
    }

    /**
     * Returns an array with elements that match `$condition` moved to the top.
     *
     * Example:
     * ```php
     * Arr::prioritize([1, 2, 3], fn($i) => $i === 2); // [2, 1, 3]
     * ```
     *
     * @template TKey of array-key
     * @template TValue
     * @param iterable<TKey, TValue> $iterable
     * Iterable to be traversed.
     * @param Closure(TValue, TKey): bool $condition
     * User defined condition callback. The callback must return a boolean value.
     * @param int|null $limit
     * [Optional] Limits the number of items to prioritize.
     * @param bool|null $reindex
     * [Optional] Result will be re-indexed if **true**.
     * If **null**, the result will be re-indexed only if it's a list.
     * Defaults to **null**.
     * @return array<TKey, TValue>
     */
    public static function prioritize(
        iterable $iterable,
        Closure $condition,
        ?int $limit = null,
        ?bool $reindex = null,
    ): array
    {
        $array = self::from($iterable);
        $isList = array_is_list($array);
        $reindex ??= $isList;

        $prioritized = [];
        $remains = [];
        $count = 0;
        $limit ??= PHP_INT_MAX;
        foreach ($array as $key => $val) {
            if ($count < $limit && self::verifyBool($condition, $key, $val)) {
                $isList
                    ? $prioritized[] = $val
                    : $prioritized[$key] = $val;
                $count++;
            } else {
                $isList
                    ? $remains[] = $val
                    : $remains[$key] = $val;
            }
        }

        $result = self::merge($prioritized, $remains);

        return $reindex
            ? array_values($result)
            : $result;
    }

    /**
     * Get the sum of the elements inside `$iterable`.
     * The elements must be af type int or float.
     * Returns `1` if empty.
     * Throws `InvalidElementException` if the iterable contains NAN.
     *
     * Example:
     * ```php
     * Arr::product([1, 2, 3, 4]); // 24
     * Arr::product(['b' => 1, 'a' => 2]); // 2
     * Arr::product([]) // 1
     * ```
     *
     * @template TKey of array-key
     * @template TValue of int|float
     * @param iterable<TKey, TValue> $iterable
     * Iterable to be traversed.
     * @return TValue
     */
    public static function product(
        iterable $iterable,
    ): mixed
    {
        $product = 1;
        foreach ($iterable as $val) {
            $product *= $val;
        }

        if (is_float($product) && is_nan($product)) {
            throw new InvalidElementException('$iterable cannot contain NAN.', [
                'iterable' => $iterable,
            ]);
        }

        /** @var TValue */
        return $product;
    }

    /**
     * Removes the given key from `&$array` and returns the pulled value.
     * If the `&$array` is a list, the list will be re-indexed.
     * Throws `InvalidKeyException` if `$key` is not found.
     *
     * Example:
     * ```php
     * $array = ['a' => 1];
     * Arr::pull($array, 'a'); // 1
     * Arr::pull($array, 'a'); // InvalidKeyException
     * ```
     *
     * @template TKey of array-key
     * @template TValue
     * @param array<TKey, TValue> $array
     * @param-out array<TKey, TValue> $array
     * [Reference] Array to be pulled.
     * @param TKey $key
     * Key to be pulled from the array.
     * @param bool|null $reindex
     * [Optional] Result will be re-indexed if **true**.
     * If **null**, the result will be re-indexed only if it's a list.
     * Defaults to **null**.
     * @return TValue
     */
    public static function pull(
        array &$array,
        int|string $key,
        ?bool $reindex = null,
    ): mixed
    {
        $result = self::pullOr($array, $key, self::miss(), $reindex);

        if ($result instanceof self) {
            throw new InvalidKeyException("Tried to pull undefined key \"$key\".", [
                'array' => $array,
                'key' => $key,
            ]);
        }

        return $result;
    }

    /**
     * Removes the given key from the array and returns the pulled value.
     * If `$key` is not found, value of `$default` is returned instead.
     * If `&$array` is a list, the list will be re-indexed.
     *
     * Example:
     * ```php
     * $array = ['a' => 1];
     * Arr::pullOr($array, 'a', -1); // 1
     * Arr::pullOr($array, 'a', -1); // -1
     * ```
     *
     * @template TKey of array-key
     * @template TValue
     * @template TDefault
     * @param array<TKey, TValue> $array
     * @param-out array<TKey, TValue> $array
     * [Reference] Array to be pulled.
     * @param TKey $key
     * Key to be pulled from the array.
     * @param TDefault $default
     * Default value to be returned if `$key` is not found.
     * @param bool|null $reindex
     * [Optional] Result will be re-indexed if **true**.
     * If **null**, the result will be re-indexed only if it's a list.
     * Defaults to **null**.
     * @return TValue|TDefault
     */
    public static function pullOr(
        array &$array,
        int|string $key,
        mixed $default,
        ?bool $reindex = null,
    ): mixed
    {
        if (!array_key_exists($key, $array)) {
            return $default;
        }

        $reindex ??= array_is_list($array);

        $value = $array[$key];
        unset($array[$key]);

        if ($reindex) {
            self::reindex($array);
        }

        return $value;
    }

    /**
     * Removes `$key` from `&$array` and returns the pulled value.
     * If `$key` is not found, **null** is returned instead.
     * If `&$array` is a list, the list will be re-indexed.
     *
     * Example:
     * ```php
     * $array = ['a' => 1];
     * Arr::pullOrNull($array, 'a'); // 1
     * Arr::pullOrNull($array, 'a'); // null
     * ```
     *
     * @template TKey of array-key
     * @template TValue
     * @param array<TKey, TValue> $array
     * @param-out array<TKey, TValue> $array
     * [Reference] Array to be pulled.
     * @param TKey $key
     * Key to be pulled from the array.
     * @param bool|null $reindex
     * [Optional] Result will be re-indexed if **true**.
     * If **null**, the result will be re-indexed only if it's a list.
     * Defaults to **null**.
     * @return TValue|null
     */
    public static function pullOrNull(
        array &$array,
        int|string $key,
        ?bool $reindex = null,
    ): mixed
    {
        return self::pullOr($array, $key, null, $reindex);
    }

    /**
     * Removes `$keys` from the `&$array` and returns the pulled values as list.
     * If `&$array` is a list, the list will be re-indexed.
     * If `$key` does not exist, the missing key will be added to `$missed`.
     *
     * Example:
     * ```php
     * $array = ['a' => 1, 'b' => 2, 'c' => 3];
     * Arr::pullMany($array, 'a'); // ['b' => 2, 'c' => 3]
     * Arr::pullMany($array, 'a'); // []
     * ```
     *
     * @template TKey of array-key
     * @template TValue
     * @param array<TKey, TValue> $array
     * @param-out array<TKey, TValue> $array
     * [Reference] Array to be pulled.
     * @param iterable<TKey> $keys
     * Keys or indexes to be pulled from the array.
     * @param bool|null $reindex
     * [Optional] Result will be re-indexed if **true**.
     * If **null**, the result will be re-indexed only if it's a list.
     * Defaults to **null**.
     * @param list<TKey>|null $missed
     * @param-out list<TKey>|null $missed
     * [Optional][Reference] `$keys` that did not exist in `$array`.
     * @return array<TKey, TValue>
     */
    public static function pullMany(
        array &$array,
        iterable $keys,
        ?bool $reindex = null,
        ?array &$missed = null,
    ): array
    {
        $reindex ??= array_is_list($array);

        $pulled = [];
        foreach ($keys as $key) {
            if (array_key_exists($key, $array)) {
                $value = $array[$key];
                unset($array[$key]);
                $pulled[$key] = $value;
            } else {
                $missed ??= [];
                $missed[] = $key;
            }
        }

        if ($reindex) {
            self::reindex($array);
        }

        return $pulled;
    }

    /**
     * Pushes values to the end of the given list (reference).
     * Throws `TypeMismatchException` if map is given.
     *
     * Example:
     * ```php
     * $array = [1, 2]; Arr::push($array, 3); // [1, 2, 3]
     * $array = [1, 2]; Arr::push($array, 3, 4); // [1, 2, 3, 4]
     * $array = ['a' => 1]; Arr::push($array, 1); // TypeMismatchException
     * ```
     *
     * @template T
     * @param array<T> $array
     * @param-out array<T> $array
     * Array reference which the value is getting push to.
     * @param T ...$values
     * Value(s) to be pushed on to the array.
     * @return void
     */
    public static function push(
        array &$array,
        mixed ...$values,
    ): void
    {
        if (!array_is_list($array)) {
            throw new TypeMismatchException('$array must be a list, map given.', [
                'array' => $array,
                'values' => $values,
            ]);
        }

        array_push($array, ...$values);
    }

    /**
     * Returns the ratio of values that satisfy the `$condition`.
     * Throws `EmptyNotAllowedException` if `$iterable` is empty.
     *
     * Example:
     * ```php
     * Arr::ratio([1, 2, 3], fn($r, $v) => true); // 1.0
     * Arr::ratio([0, 1, 1], fn($r, $v) => false); // 0.0
     * Arr::ratio([], fn($r, $v) => true); // null
     * ```
     *
     * @template TKey of array-key
     * @template TValue
     * @param iterable<TKey, TValue> $iterable
     * Iterable to be traversed.
     * @param Closure(TValue, TKey): bool $condition
     * User defined condition callback. The callback must return a boolean value.
     * @return float
     */
    public static function ratio(iterable $iterable, Closure $condition): float
    {
        $ratio = self::ratioOrNull($iterable, $condition);

        if ($ratio !== null) {
            return $ratio;
        }

        throw new EmptyNotAllowedException('$iterable must contain at least one element.', [
            'iterable' => $iterable,
            'condition' => $condition,
        ]);
    }

    /**
     * Returns the ratio of values that satisfy the `$condition`.
     * Returns **null** if `$iterable` is empty.
     *
     * Example:
     * ```php
     * Arr::ratioOrNull([1, 2, 3], fn($r, $v) => true); // 1.0
     * Arr::ratioOrNull([0, 1, 1], fn($r, $v) => false); // 0.0
     * Arr::ratioOrNull([], fn($r, $v) => true); // null
     * ```
     *
     * @template TKey of array-key
     * @template TValue
     * @param iterable<TKey, TValue> $iterable
     * Iterable to be traversed.
     * @param Closure(TValue, TKey): bool $condition
     * User defined condition callback. The callback must return a boolean value.
     * @return float|null
     */
    public static function ratioOrNull(iterable $iterable, Closure $condition): ?float
    {
        $total = 0;
        $trues = 0;
        foreach ($iterable as $key => $value) {
            $total++;
            if(self::verifyBool($condition, $key, $value)) {
                $trues++;
            }
        }

        if ($total === 0) {
            return null;
        }

        return (float) ($trues / $total);
    }

    /**
     * Iteratively reduce `$iterable` to a single value by invoking
     * `$callback($reduced, $val, $key)`.
     * Throws `EmptyNotAllowedException` if `$iterable` is empty.
     *
     * Example:
     * ```php
     * Arr::reduce([1, 2, 3], fn($r, $v) => $r + $v); // 6
     * ```
     *
     * @template TKey of array-key
     * @template TValue
     * @param iterable<TKey, TValue> $iterable
     * Iterable to be traversed.
     * @param Closure(TValue, TValue, TKey): TValue $callback
     * First argument contains the reduced value.
     * Second argument contains the current value.
     * Third argument contains the current key.
     * @return TValue
     */
    public static function reduce(
        iterable $iterable,
        Closure $callback,
    ): mixed
    {
        $result = self::reduceOr($iterable, $callback, self::miss());

        if ($result instanceof self) {
            throw new EmptyNotAllowedException('$iterable must contain at least one element.', [
                'iterable' => $iterable,
                'callback' => $callback,
            ]);
        }

        return $result;
    }

    /**
     * Iteratively reduce `$iterable` to a single value by invoking
     * `$callback($reduced, $val, $key)`.
     * Returns `$default` if `$iterable` is empty.
     *
     * Example:
     * ```php
     * Arr::reduceOr([1, 2, 3], fn($r, $v) => $r + $v); // 6
     * Arr::reduceOr([], fn($r, $v) => $r + $v, 'z'); // 'z'
     * ```
     *
     * @template TKey of array-key
     * @template TValue
     * @template TDefault
     * @param iterable<TKey, TValue> $iterable
     * Iterable to be traversed.
     * @param Closure(TValue, TValue, TKey): TValue $callback
     * First argument contains the reduced value.
     * Second argument contains the current value.
     * Third argument contains the current key.
     * @param TDefault $default
     * Value that is used when `$iterable` is empty.
     * @return TValue|TDefault
     */
    public static function reduceOr(
        iterable $iterable,
        Closure $callback,
        mixed $default,
    ): mixed
    {
        $result = null;
        $initialized = false;
        foreach ($iterable as $key => $val) {
            if (!$initialized) {
                $result = $val;
                $initialized = true;
            } else {
                $result = $callback($result, $val, $key);
            }
        }

        return $initialized
            ? $result
            : $default;
    }

    /**
     * Iteratively reduce `$iterable` to a single value by invoking
     * `$callback($reduced, $val, $key)`.
     * Returns **null** if `$iterable` is empty.
     *
     * Example:
     * ```php
     * Arr::reduceOrNull([1, 2, 3], fn($r, $v) => $r + $v); // 6
     * Arr::reduceOrNull([], fn($r, $v) => $r + $v, 'z'); // null
     * ```
     *
     * @template TKey of array-key
     * @template TValue
     * @param iterable<TKey, TValue> $iterable
     * Iterable to be traversed.
     * @param Closure(TValue, TValue, TKey): TValue $callback
     * First argument contains the reduced value.
     * Second argument contains the current value.
     * Third argument contains the current key.
     * @return TValue|null
     */
    public static function reduceOrNull(
        iterable $iterable,
        Closure $callback,
    ): mixed
    {
        $result = self::reduceOr($iterable, $callback, self::miss());

        return ($result instanceof self)
            ? null
            : $result;
    }

    /**
     * Given array will be converted into list.
     *
     * Example:
     * ```php
     * $array = ['a' => 1];
     * Arr::reindex($array); // $array will be [1]
     * ```
     *
     * @template TKey of array-key
     * @template TValue
     * @param array<TKey, TValue> $array
     * @param-out array<TKey, TValue> $array
     * [Reference] Array to be re-indexed.
     * @return void
     */
    public static function reindex(
        array &$array,
    ): void
    {
        if (array_is_list($array)) {
            return;
        }

        $placeholder = [];
        foreach ($array as $key => $val) {
            unset($array[$key]);
            $placeholder[] = $val;
        }
        foreach ($placeholder as $i => $val) {
            $array[$i] = $val;
        }
    }

    /**
     * Removes `$value` from `&$array`.
     * Limit can be set to specify the number of times a value should be removed.
     * Returns the keys of the removed value.
     *
     * Example:
     * ```php
     * $map = ['a' => 1, 'b' => 2, 'c' => 1];
     * Arr::remove($map, 1); // ['a', 'c'] and $map will be changed to ['b' => 2]
     * ```
     *
     * @template TKey of array-key
     * @template TValue
     * @param array<TKey, TValue> $array
     * @param-out array<TKey, TValue> $array
     * [Reference] Array to have the value removed.
     * @param TValue $value
     * Value to be removed.
     * @param int|null $limit
     * [Optional] Limits the number of items to be removed.
     * @param bool|null $reindex
     * [Optional] Result will be re-indexed if **true**.
     * If **null**, the result will be re-indexed only if it's a list.
     * Defaults to **null**.
     * @return list<TKey>
     */
    public static function remove(
        array &$array,
        mixed $value,
        ?int $limit = null,
        ?bool $reindex = null,
    ): array
    {
        $count = 0;
        $limit ??= PHP_INT_MAX;
        $removed = [];

        // Must check before processing, since unset converts lists to assoc array.
        $reindex ??= array_is_list($array);

        foreach ($array as $key => $val) {
            if ($count < $limit && $val === $value) {
                unset($array[$key]);
                $removed[] = $key;
                ++$count;
            }
        }

        // if the list is an array, use array_splice to re-index
        if ($count > 0 && $reindex) {
            self::reindex($array);
        }

        return $removed;
    }

    /**
     * Returns an array which contains `$iterable` for a given number of times.
     * Note: All keys will be discarded.
     *
     * Example
     * ```php
     * Arr::repeat([1, 2], 2); // [1, 2, 1, 2]
     * ```
     *
     * @template TKey of array-key
     * @template TValue
     * @param iterable<TKey, TValue> $iterable
     * Iterable to be traversed.
     * @param int<0, max> $times
     * Number of times `$iterable` will be repeated.
     * @return list<TValue>
     */
    public static function repeat(
        iterable $iterable,
        int $times,
    ): array
    {
        return iterator_to_array(Iter::repeat($iterable, $times), false);
    }

    /**
     * Returns an array which contains keys and values from `$iterable`
     * but with the `$search` value replaced with the `$replacement` value.
     *
     * Example:
     * ```php
     * Arr::replace([3, 1, 3], 3, 0); // [0, 1, 0]
     * Arr::replace(['a' => 1], 1, 2); // ['a' => 2]
     * ```
     *
     * @template TKey of array-key
     * @template TValue
     * @param iterable<TKey, TValue> $iterable
     * Iterable to be traversed.
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
     * @return array<TKey, TValue>
     */
    public static function replace(
        iterable $iterable,
        mixed $search,
        mixed $replacement,
        ?int $limit = null,
        int &$count = 0,
    ): array
    {
        return iterator_to_array(
            Iter::replace($iterable, $search, $replacement, $limit, $count),
        );
    }

    /**
     * Returns an array which contain all elements of `$iterable` in reverse order.
     *
     * Example:
     * ```php
     * Arr::reverse([1, 2]); // [2, 1]
     * Arr::reverse(['a' => 1, 'b' => 2]); // ['b' => 2, 'a' => 1]
     * ```
     *
     * @template TKey of array-key
     * @template TValue
     * @param iterable<TKey, TValue> $iterable
     * Iterable to be traversed.
     * @param bool|null $reindex
     * [Optional] Result will be re-indexed if **true**.
     * If **null**, the result will be re-indexed only if it's a list.
     * Defaults to **null**.
     * @return array<TKey, TValue>
     */
    public static function reverse(
        iterable $iterable,
        ?bool $reindex = null,
    ): array
    {
        $array = self::from($iterable);
        $preserveKeys = !($reindex ?? array_is_list($array));
        return array_reverse($array, $preserveKeys);
    }

    /**
     * Converts `$iterable` to an array and rotate the array to the right
     * by `$steps`. If `$steps` is a negative value, the array will rotate
     * to the left instead.
     *
     * Example:
     * ```php
     * Arr::rotate([1, 2, 3], 1);  // [2, 3, 1]
     * Arr::rotate([1, 2, 3], -1); // [3, 1, 2]
     * Arr::rotate(['a' => 1, 'b' => 2, 'c' => 3], 1); // ['b' => 2, 'c' => 3, 'a' => 1]
     * ```
     *
     * @template TKey of array-key
     * @template TValue
     * @param iterable<TKey, TValue> $iterable
     * Iterable to be traversed.
     * @param int $steps
     * Number of times the key/value will be rotated.
     * @param bool|null $reindex
     * [Optional] Result will be re-indexed if **true**.
     * If **null**, the result will be re-indexed only if it's a list.
     * Defaults to **null**.
     * @return array<TKey, TValue>
     */
    public static function rotate(
        iterable $iterable,
        int $steps,
        ?bool $reindex = null,
    ): array
    {
        $array = self::from($iterable);
        $ptr = 0;
        $result = [];
        $rotated = [];

        if ($steps < 0) {
            $steps = count($array) + $steps;
        }

        if ($steps !== 0) {
            foreach ($array as $key => $val) {
                if ($ptr < $steps) {
                    $rotated[$key] = $val;
                } else {
                    $result[$key] = $val;
                }
                ++$ptr;
            }

            foreach ($rotated as $key => $val) {
                $result[$key] = $val;
            }
        }

        return ($reindex ?? array_is_list($array))
            ? array_values($result)
            : $result;
    }

    /**
     * Returns a random element from `$iterable`.
     * Throws `EmptyNotAllowedException` if `$iterable` is empty.
     *
     * Example:
     * ```php
     * Arr::sample(['a', 'b', 'c']); // 'b'
     * Arr::sample([]); // EmptyNotAllowedException
     * ```
     *
     * @template TKey of array-key
     * @template TValue
     * @param iterable<TKey, TValue> $iterable
     * Iterable to be sampled.
     * @param Randomizer|null $randomizer
     * [Optional] Randomizer to be used.
     * Secure randomizer will be used if **null**.
     * Defaults to **null**.
     * @return TValue
     */
    public static function sample(
        iterable $iterable,
        ?Randomizer $randomizer = null,
    ): mixed
    {
        $array = self::from($iterable);

        return $array[self::sampleKey($array, $randomizer)];
    }

    /**
     * Returns a random key picked from `$iterable`.
     * Throws `EmptyNotAllowedException` if `$iterable` is empty.
     *
     * Example:
     * ```php
     * Arr::sampleKey(['a', 'b', 'c']); // 1
     * Arr::sampleKey(['a' => 1, 'b' => 2, 'c' => 3]); // 'c'
     * ```
     *
     * @template TKey of array-key
     * @template TValue
     * @param iterable<TKey, TValue> $iterable
     * Iterable to be traversed.
     * @param Randomizer|null $randomizer
     * [Optional] Randomizer to be used.
     * Default randomizer (Secure) will be used if **null**.
     * Defaults to **null**.
     * @return TKey
     */
    public static function sampleKey(
        iterable $iterable,
        ?Randomizer $randomizer = null,
    ): mixed
    {
        $key = self::sampleKeyOrNull($iterable, $randomizer);

        if ($key === null) {
            throw new EmptyNotAllowedException('$iterable must contain at least one element.', [
                'iterable' => $iterable,
                'randomizer' => $randomizer,
            ]);
        }

        /** @var TKey $key */
        return $key;
    }

    /**
     * Returns a random key picked from `$iterable`.
     * Returns **null** if `$iterable` is empty.
     *
     * Example:
     * ```php
     * Arr::sampleKey(['a', 'b', 'c']); // 1
     * Arr::sampleKey(['a' => 1, 'b' => 2, 'c' => 3]); // 'c'
     * ```
     *
     * @template TKey of array-key
     * @template TValue
     * @param iterable<TKey, TValue> $iterable
     * Iterable to be traversed.
     * @param Randomizer|null $randomizer
     * [Optional] Randomizer to be used.
     * Default randomizer (Secure) will be used if **null**.
     * Defaults to **null**.
     * @return TKey|null
     */
    public static function sampleKeyOrNull(
        iterable $iterable,
        ?Randomizer $randomizer = null,
    ): mixed
    {
        $array = self::from($iterable);

        if (count($array) === 0) {
            return null;
        }

        return self::sampleKeys($array, 1, false, $randomizer)[0];
    }

    /**
     * Returns a list of random elements picked from `$iterable`.
     * If `$replace` is set to **false**, each key will be chosen only once.
     * Throws `InvalidArgumentException` if `$amount` is larger than `$iterable`'s size.
     *
     * Example:
     * ```php
     * Arr::sampleKeys(['a', 'b', 'c'], 2); // [0, 2]
     * Arr::sampleKeys(['a' => 1, 'b' => 2, 'c' => 3], 2); // ['a', 'c'] <- without replacement
     * Arr::sampleKeys(['a' => 1, 'b' => 2, 'c' => 3], 2, true); // ['b', 'b'] <- with replacement
     * ```
     *
     * @template TKey of array-key
     * @template TValue
     * @param iterable<TKey, TValue> $iterable
     * Iterable to be traversed.
     * @param int $amount
     * Amount of items to sample.
     * @param bool $replace
     * If **true**, same elements can be chosen more than once.
     * Defaults to **false**.
     * @param Randomizer|null $randomizer
     * [Optional] Randomizer to be used.
     * Default randomizer (Secure) will be used if **null**.
     * Defaults to **null**.
     * @return list<TKey>
     */
    public static function sampleKeys(
        iterable $iterable,
        int $amount,
        bool $replace = false,
        ?Randomizer $randomizer = null,
    ): array
    {
        $randomizer ??= self::getDefaultRandomizer();
        $array = self::from($iterable);
        $size = count($array);

        if ($size === 0) {
            throw new EmptyNotAllowedException('$iterable must contain at least one element.', [
                'iterable' => $iterable,
                'randomizer' => $randomizer,
            ]);
        }

        if ($amount < 0 || (!$replace && $amount > $size)) {
            throw new InvalidArgumentException('$amount must be between 0 and size of $iterable.', [
                'iterable' => $iterable,
                'amount' => $amount,
                'replace' => $replace,
            ]);
        }

        if ($amount === 0) {
            return [];
        }

        if (!$replace) {
            // Randomizer::pickArrayKeys() returns keys in order, so we
            // shuffle the result to randomize the order as well.
            $keys = $randomizer->pickArrayKeys($array, $amount);
            $result = $randomizer->shuffleArray($keys);
            return array_values($result);
        }

        $keys = array_keys($array);
        $max = count($keys) - 1;

        $result = [];
        for ($i = 0; $i < $amount; $i++) {
            $result[] = $keys[$randomizer->getInt(0, $max)];
        }
        return $result;
    }

    /**
     * Returns a list of random elements picked from `$iterable`.
     * If `$replace` is set to **false**, each key will be chosen only once.
     * Throws `InvalidArgumentException` if `$amount` is larger than `$iterable`'s size.
     *
     * Example:
     * ```php
     * Arr::sampleMany(['a', 'b', 'c'], 2, false); // ['a', 'b'] <- without replacement
     * Arr::sampleMany(['a', 'b', 'c'], 2, true); // ['c', 'c'] <- with replacement
     * Arr::sampleMany(['a' => 1], 1); // [1] <- map will be converted to list
     * ```
     *
     * @template TKey of array-key
     * @template TValue
     * @param iterable<TKey, TValue> $iterable
     * Iterable to be traversed.
     * @param int $amount
     * Amount of items to sample.
     * @param bool $replace
     * If **true**, same elements can be chosen more than once.
     * Defaults to **false**.
     * @param Randomizer|null $randomizer
     * [Optional] Randomizer to be used.
     * Default randomizer (Secure) will be used if **null**.
     * Defaults to **null**.
     * @return list<TValue>
     */
    public static function sampleMany(
        iterable $iterable,
        int $amount,
        bool $replace = false,
        ?Randomizer $randomizer = null,
    ): array
    {
        $array = self::from($iterable);
        return array_map(
            static fn($key) => $array[$key],
            self::sampleKeys($array, $amount, $replace, $randomizer),
        );
    }

    /**
     * Returns a random element from `$iterable`.
     * Returns `$default` if `$iterable` is empty.
     *
     * Example:
     * ```php
     * Arr::sampleOr(['a', 'b', 'c'], 'z'); // 'b'
     * Arr::sampleOr([], 'z'); // 'z'
     * ```
     *
     * @template TKey of array-key
     * @template TValue
     * @template TDefault
     * @param iterable<TKey, TValue> $iterable
     * Iterable to be sampled.
     * @param TDefault $default
     * Value that is used when `$iterable` is empty.
     * @param Randomizer|null $randomizer
     * [Optional] Randomizer to be used.
     * Secure randomizer will be used if **null**.
     * Defaults to **null**.
     * @return TValue|TDefault
     */
    public static function sampleOr(
        iterable $iterable,
        mixed $default,
        ?Randomizer $randomizer = null,
    ): mixed
    {
        $array = self::from($iterable);
        $key = self::sampleKeyOrNull($array, $randomizer);

        if ($key === null) {
            return $default;
        }

        return $array[$key];
    }

    /**
     * Returns a random element from `$iterable`.
     * Returns **null** if `$iterable` is empty.
     *
     * Example:
     * ```php
     * Arr::sampleOrNull(['a', 'b', 'c'], 'z'); // 'b'
     * Arr::sampleOrNull([]); // null
     * ```
     *
     * @template TKey of array-key
     * @template TValue
     * @param iterable<TKey, TValue> $iterable
     * Iterable to be sampled.
     * @param Randomizer|null $randomizer
     * [Optional] Randomizer to be used.
     * Secure randomizer will be used if **null**.
     * Defaults to **null**.
     * @return TValue|null
     */
    public static function sampleOrNull(
        iterable $iterable,
        ?Randomizer $randomizer = null,
    ): mixed
    {
        return self::sampleOr($iterable, null, $randomizer);
    }

    /**
     * Runs the condition though each element of `$iterable` and will return **true**
     * if all iterations that run through the condition returned **true** or if
     * `$iterable` is empty, **false** otherwise.
     *
     * Example:
     * ```php
     * Arr::satisfyAll([1, 2], is_int(...)); // true
     * Arr::satisfyAll([1, 2.1], is_int(...)); // false
     * Arr::satisfyAll([]); // true
     * ```
     *
     * @template TKey of array-key
     * @template TValue
     * @param iterable<TKey, TValue> $iterable
     * Iterable to be traversed.
     * @param Closure(TValue, TKey): bool $condition
     * User defined condition callback. The callback must return a boolean value.
     * @return bool
     */
    public static function satisfyAll(
        iterable $iterable,
        Closure $condition,
    ): bool
    {
        foreach ($iterable as $key => $val) {
            if (self::verifyBool($condition, $key, $val) === false) {
                return false;
            }
        }
        return true;
    }

    /**
     * Runs the condition though each element of `$iterable` and will return **true**
     * if any iterations that run through the `$condition` returned **true**,
     * **false** otherwise (including empty iterable).
     *
     * Example:
     * ```php
     * Arr::satisfyAny([1, null, 2, false], is_null(...)); // true
     * Arr::satisfyAny([1, 2], is_float(...)); // false
     * Arr::satisfyAny([]); // false
     * ```
     *
     * @template TKey of array-key
     * @template TValue
     * @param iterable<TKey, TValue> $iterable
     * Iterable to be traversed.
     * @param Closure(TValue, TKey): bool $condition
     * User defined condition callback. The callback must return a boolean value.
     * @return bool
     */
    public static function satisfyAny(
        iterable $iterable,
        Closure $condition,
    ): bool
    {
        foreach ($iterable as $key => $val) {
            if (self::verifyBool($condition, $key, $val)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Runs the condition though each element of `$iterable` and will return **true**
     * if all the iterations that run through the `$condition` returned **false**.
     * **false** otherwise.
     *
     * Example:
     * ```php
     * Arr::satisfyNone(['a', 'b'], static fn($v) => empty($v)); // true
     * Arr::satisfyNone([1, 2.1], is_int(...)); // false
     * Arr::satisfyNone([]); // true
     * ```
     *
     * @template TKey of array-key
     * @template TValue
     * @param iterable<TKey, TValue> $iterable
     * Iterable to be traversed.
     * @param Closure(TValue, TKey): bool $condition
     * User defined condition callback. The callback must return a boolean value.
     * @return bool
     */
    public static function satisfyNone(
        iterable $iterable,
        Closure $condition,
    ): bool
    {
        foreach ($iterable as $key => $val) {
            if (self::verifyBool($condition, $key, $val)) {
                return false;
            }
        }
        return true;
    }

    /**
     * Runs the condition though each element of `$iterable` and will return **true**
     * if iterations that run through the `$condition` returned **true** only once,
     * **false** otherwise (including empty iterable).
     *
     * Example:
     * ```php
     * Arr::satisfyOnce([1, 'a'], is_int(...)); // true
     * Arr::satisfyOnce([1, 2], is_int(...)); // false
     * Arr::satisfyOnce([]); // false
     * ```
     *
     * @template TKey of array-key
     * @template TValue
     * @param iterable<TKey, TValue> $iterable
     * Iterable to be traversed.
     * @param Closure(TValue, TKey): bool $condition
     * User defined condition callback. The callback must return a boolean value.
     * @return bool
     */
    public static function satisfyOnce(
        iterable $iterable,
        Closure $condition,
    ): bool
    {
        $satisfied = false;
        foreach ($iterable as $key => $val) {
            if (self::verifyBool($condition, $key, $val)) {
                if ($satisfied) {
                    return false;
                }
                $satisfied = true;
            }
        }
        return $satisfied;
    }

    /**
     * Add or update an entry in the `$array`.
     *
     * Example:
     * ```php
     * $map = ['a' => 1];
     * Arr::set($map, 'a', 2); // $map is now ['a' => 2]
     * Arr::set($map, 'b', 3); // $map is now ['a' => 2, 'b' => 3]
     * ```
     *
     * @template TKey of array-key
     * @template TValue
     * @param array<TKey, TValue> $array
     * @param-out array<TKey, TValue> $array
     * [Reference] Array to be set.
     * @param TKey $key
     * Key to set to in the array.
     * @param TValue $value
     * Value to set in the array.
     * @return void
     */
    public static function set(
        array &$array,
        int|string $key,
        mixed $value,
    ): void
    {
        $array[$key] = $value;
    }

    /**
     * Set an entry in the `&$array` only if the entry already exists.
     *
     * Example:
     * ```php
     * $map = ['a' => 1]
     * Arr::setIfExists($map, 'a', 2); // true (and $map is now ['a' => 2])
     * Arr::setIfExists($map, 'b', 1); // false (and $map is still ['a' => 2])
     * ```
     *
     * @template TKey of array-key
     * @template TValue
     * @param array<TKey, TValue> $array
     * @param-out array<TKey, TValue> $array
     * [Reference] Array to be set.
     * @param TKey $key
     * Key to set to in the array.
     * @param TValue $value
     * Value to set in the array.
     * @return bool
     * **true** if set, **false** otherwise.
     */
    public static function setIfExists(
        array &$array,
        int|string $key,
        mixed $value,
    ): bool
    {
        if (self::containsKey($array, $key)) {
            self::set($array, $key, $value);
            return true;
        }
        return false;
    }

    /**
     * Set an entry in the `&$array` only if the entry does not exist.
     *
     * Example:
     * ```php
     * $map = ['a' => 1]
     * Arr::setIfNotExists($map, 'a', 2); // false (and $map is still ['a' => 1])
     * Arr::setIfNotExists($map, 'b', 1); // true (and $map is now ['a' => 1, 'b' => 1])
     * ```
     *
     * @template TKey of array-key
     * @template TValue
     * @param array<TKey, TValue> $array
     * Reference to the target array.
     * @param TKey $key
     * Key to set to in the array.
     * @param TValue $value
     * Value to set in the array.
     * @return bool
     * **true** if set, **false** otherwise.
     */
    public static function setIfNotExists(
        array &$array,
        int|string $key,
        mixed $value,
    ): bool
    {
        if (self::doesNotContainKey($array, $key)) {
            self::set($array, $key, $value);
            return true;
        }
        return false;
    }

    /**
     * Shift an element off the beginning of `&$array`.
     * Throws a `EmptyNotAllowedException` if `&$array` is empty.
     *
     * Example:
     * ```php
     * $list = [1, 2];
     * Arr::shift($list); // 1 ($list is now [2])
     *
     * $empty = [];
     * Arr::shift($empty); // EmptyNotAllowedException
     * ```
     *
     * @template TKey of array-key
     * @template TValue
     * @param array<TKey, TValue> $array
     * @param-out array<TKey, TValue> $array
     * [Reference] Array to be shifted.
     * @return TValue
     * The shifted value.
     */
    public static function shift(
        array &$array,
    ): mixed
    {
        $shifted = self::shiftOrNull($array);

        if ($shifted === null) {
            throw new EmptyNotAllowedException('&$array must contain at least one element.', [
                'array' => $array,
            ]);
        }

        return $shifted;
    }

    /**
     * Shift an element off the beginning of `&$array` up to `$amount`.
     * Returns the shifted elements as an array.
     *
     * Example:
     * ```php
     * $list = [1, 2, 3];
     * Arr::shiftMany($list, 2); // [1, 2] ($list is now [3])
     * ```
     *
     * @template TKey of array-key
     * @template TValue
     * @param array<TKey, TValue> $array
     * @param-out array<TKey, TValue> $array
     * [Reference] Array to be shifted.
     * @param int $amount
     * Amount of elements to be shifted.
     * Must be an integer with value >= 1.
     * @return array<TKey, TValue>
     * Elements that were shifted.
     */
    public static function shiftMany(
        array &$array,
        int $amount,
    ): array
    {
        if ($amount <= 0) {
            throw new InvalidArgumentException("Expected: \$amount >= 1. Got: {$amount}.", [
                'array' => $array,
                'amount' => $amount,
            ]);
        }
        return array_splice($array, 0, $amount);
    }

    /**
     * Shift an element off the beginning of `&$array`.
     * Returns **null** if `&$array` is empty.
     *
     * Example:
     * ```php
     * $list = [1, 2];
     * Arr::shiftOrNull($list); // 1 ($list is now [2])
     *
     * $empty = [];
     * Arr::shiftOrNull($empty); // null
     * ```
     *
     * @template TKey of array-key
     * @template TValue
     * @param array<TKey, TValue> $array
     * @param-out array<TKey, TValue> $array
     * [Reference] Array to be shifted.
     * @return TValue|null
     * The shifted value.
     */
    public static function shiftOrNull(
        array &$array,
    ): mixed
    {
        return array_shift($array);
    }

    /**
     * Converts `$iterable` to array and shuffles the array.
     *
     * @template TKey of array-key
     * @template TValue
     * @param iterable<TKey, TValue> $iterable
     * Iterable to be traversed.
     * @param bool|null $reindex
     * [Optional] Result will be re-indexed if **true**.
     * If **null**, the result will be re-indexed only if it's a list.
     * Defaults to **null**.
     * @param Randomizer|null $randomizer
     * [Optional] Randomizer to be used.
     * Default randomizer (Secure) will be used if **null**.
     * Defaults to **null**.
     * @return array<TKey, TValue>
     */
    public static function shuffle(
        iterable $iterable,
        ?bool $reindex = null,
        ?Randomizer $randomizer = null,
    ): array
    {
        $randomizer ??= self::getDefaultRandomizer();
        $array = self::from($iterable);
        $reindex ??= array_is_list($array);

        $keys = $randomizer->shuffleArray(array_keys($array));

        $shuffled = [];
        foreach ($keys as $key) {
            $shuffled[$key] = $array[$key];
        }

        return $reindex
            ? self::values($shuffled)
            : $shuffled;
    }

    /**
     * Returns the only element in the `$iterable`.
     * If `$condition` is also given, the sole element of a sequence that satisfies a specified
     * condition is returned instead.
     * Throws `InvalidArgumentException` if there are more than one element in `$iterable`.
     * Throws `NoMatchFoundException` if no condition is met.
     * Throws `EmptyNotAllowedException` if `$iterable` is empty.
     *
     * Example:
     * ```php
     * Arr::single([1]); // 1
     * Arr::single([1, 2]); // InvalidArgumentException
     * ```
     *
     * @template TKey of array-key
     * @template TValue
     * @param iterable<TKey, TValue> $iterable
     * Iterable to be traversed.
     * @param Closure(TValue, TKey): bool|null $condition
     * [Optional] User defined condition callback. The callback must return a boolean value.
     * Defaults to **null**.
     * @return TValue
     */
    public static function single(
        iterable $iterable,
        ?Closure $condition = null,
    ): mixed
    {
        $found = self::miss();
        $count = 0;
        foreach ($iterable as $key => $val) {
            if ($condition === null || $condition($val, $key)) {
                ++$count;
                $found = $val;
            }
        }

        if ($count > 1) {
            throw new InvalidArgumentException("Expected only one element in result. $count given.", [
                'iterable' => $iterable,
                'condition' => $condition,
                'count' => $count,
            ]);
        }

        if ($found instanceof self) {
            $exception = ($condition !== null)
                ? new NoMatchFoundException('Failed to find matching condition.')
                : new EmptyNotAllowedException('$iterable must contain at least one element.');
            throw $exception->setContext([
                'iterable' => $iterable,
                'condition' => $condition,
                'count' => $count,
            ]);
        }

        return $found;
    }

    /**
     * Returns a shallow copy of a portion of an iterable into a new array.
     *
     * Example:
     * ```php
     * Arr::slice([1, 2, 3, 4], 1, 2); // [2, 3]
     * Arr::slice([1, 2, 3], -2); // [2, 3]
     * ```
     *
     * @template TKey of array-key
     * @template TValue
     * @param iterable<TKey, TValue> $iterable
     * Iterable to be traversed.
     * @param int $offset
     * Starting position of the slice.
     * @param int $length
     * Length of the slice.
     * [Optional] Defaults to `INT_MAX`.
     * @param bool|null $reindex
     * [Optional] Result will be re-indexed if **true**.
     * If **null**, the result will be re-indexed only if it's a list.
     * Defaults to **null**.
     * @return array<TKey, TValue>
     */
    public static function slice(
        iterable $iterable,
        int $offset,
        int $length = PHP_INT_MAX,
        ?bool $reindex = null,
    ): array
    {
        $array = self::from($iterable);
        $reindex ??= array_is_list($array);
        return iterator_to_array(Iter::slice($array, $offset, $length, $reindex));
    }

    /**
     * Converts `$iterable` to an overlapping sub-slices of `$size`.
     * Also known as sliding window.
     *
     * Example:
     * ```php
     * Arr::windows(range(0, 4), 3) // [[0, 1, 2], [1, 2, 3], [2, 3, 4]]
     * Arr::windows(['a' => 1, 'b' => 2, 'c' => 3], 2) // [['a' => 1, 'b' => 2], ['b' => 2, 'c' => 3]]
     * ```
     *
     * @template TKey of array-key
     * @template TValue
     * @param iterable<TKey, TValue> $iterable
     * Iterable to be traversed.
     * @param int $size
     * Size of the window. Must be >= 1.
     * @param bool|null $reindex
     * [Optional] Result will be re-indexed if **true**.
     * If **null**, the result will be re-indexed only if it's a list.
     * @return list<array<TKey, TValue>>
     */
    public static function slide(
        iterable $iterable,
        int $size,
        ?bool $reindex = null,
    ): array
    {
        if ($reindex === null) {
            $iterable = self::from($iterable);
            $reindex = array_is_list($iterable);
        }
        return Arr::values(Iter::slide($iterable, $size, $reindex));
    }

    /**
     * Sort the `$iterable` by value in the given order.
     *
     * @param iterable<TKey, TValue> $iterable
     * Iterable to be traversed.
     * @param SortOrder $order
     * Order of the sort.
     * @param Closure(TValue, TKey): mixed|null $by
     * [Optional] User defined comparison callback.
     * The value returned will be used to sort the array.
     * @param int $flag
     * Sort flag to change the behavior of the sort.
     * Defaults to `SORT_REGULAR`.
     * @param bool|null $reindex
     * Result will be re-indexed if **true**.
     * If **null**, the result will be re-indexed only if it's a list.
     * Defaults to **null**.
     * @return array<TKey, TValue>
     * @see self::sortDesc()
     *
     * @template TKey of array-key
     * @template TValue
     * @see self::sortAsc()
     */
    public static function sort(
        iterable $iterable,
        SortOrder $order,
        ?Closure $by = null,
        int $flag = SORT_REGULAR,
        ?bool $reindex = null,
    ): array
    {
        $copy = self::from($iterable);
        $reindex ??= array_is_list($copy);

        if ($by !== null) {
            $refs = self::map($copy, $by);
            match ($order) {
                SortOrder::Ascending => asort($refs, $flag),
                SortOrder::Descending => arsort($refs, $flag),
            };
            $sorted = self::map($refs, fn($val, $key) => $copy[$key]);
        } else {
            $sorted = $copy;
            match ($order) {
                SortOrder::Ascending => asort($sorted, $flag),
                SortOrder::Descending => arsort($sorted, $flag),
            };
        }

        return $reindex
            ? array_values($sorted)
            : $sorted;
    }

    /**
     * Sort the `$iterable` by value in ascending order.
     *
     * Example:
     * ```php
     * Arr::sort([2, 0, 1]);  // [0, 1, 2]
     * Arr::sort(['b' => 2, 'a' => 1]);  // ['a' => 1, 'b' => 2]
     * ```
     *
     * @template TKey of array-key
     * @template TValue
     * @param iterable<TKey, TValue> $iterable
     * Iterable to be traversed.
     * @param Closure(TValue, TKey): mixed|null $by
     * [Optional] User defined comparison callback.
     * The value returned will be used to sort the array.
     * @param int $flag
     * [Optional] Sort flag to change the behavior of the sort.
     * See https://www.php.net/manual/en/function.sort.php for more info.
     * Defaults to `SORT_REGULAR`.
     * @param bool|null $reindex
     * [Optional] Result will be re-indexed if **true**.
     * If **null**, the result will be re-indexed only if it's a list.
     * Defaults to **null**.
     * @return array<TKey, TValue>
     */
    public static function sortAsc(
        iterable $iterable,
        ?Closure $by = null,
        int $flag = SORT_REGULAR,
        ?bool $reindex = null,
    ): array
    {
        return self::sort($iterable, SortOrder::Ascending, $by, $flag, $reindex);
    }

    /**
     * Sort `$iterable` by key in ascending order.
     *
     * Example:
     * ```php
     * Arr::sortByKey(['b' => 0, 'a' => 1]);  // ['a' => 1, 'b' => 0]
     * ```
     *
     * @template TKey of array-key
     * @template TValue
     * @param iterable<TKey, TValue> $iterable
     * Iterable to be traversed.
     * @param bool $ascending
     * Sort in ascending order if **true**, descending order if **false**.
     * @param int $flag
     * [Optional] Sort flag to change the behavior of the sort.
     * See https://www.php.net/manual/en/function.sort.php for more info.
     * Defaults to `SORT_REGULAR`.
     * @return array<TKey, TValue>
     */
    public static function sortByKey(
        iterable $iterable,
        bool $ascending,
        int $flag = SORT_REGULAR,
    ): array
    {
        $copy = self::from($iterable);
        $ascending
            ? ksort($copy, $flag)
            : krsort($copy, $flag);
        return $copy;
    }

    /**
     * Sort the `$iterable` by key in ascending order.
     *
     * Example:
     * ```php
     * Arr::sortByKey(['b' => 0, 'a' => 1]);  // ['a' => 1, 'b' => 0]
     * ```
     *
     * @template TKey of array-key
     * @template TValue
     * @param iterable<TKey, TValue> $iterable
     * Iterable to be traversed.
     * @param int $flag
     * [Optional] Sort flag to change the behavior of the sort.
     * See https://www.php.net/manual/en/function.sort.php for more info.
     * Defaults to `SORT_REGULAR`.
     * @return array<TKey, TValue>
     */
    public static function sortByKeyAsc(
        iterable $iterable,
        int $flag = SORT_REGULAR,
    ): array
    {
        return self::sortByKey($iterable, true, $flag);
    }

    /**
     * Sort the `$iterable` by key in descending order.
     *
     * Example:
     * ```php
     * Arr::sortByKeyDesc(['a' => 1, 'b' => 2]);  // ['b' => 2, 'a' => 1]
     * ```
     *
     * @template TKey of array-key
     * @template TValue
     * @param iterable<TKey, TValue> $iterable
     * Iterable to be traversed.
     * @param int $flag
     * [Optional] Sort flag to change the behavior of the sort.
     * See https://www.php.net/manual/en/function.sort.php for more info.
     * Defaults to `SORT_REGULAR`.
     * @return array<TKey, TValue>
     */
    public static function sortByKeyDesc(
        iterable $iterable,
        int $flag = SORT_REGULAR,
    ): array
    {
        return self::sortByKey($iterable, false, $flag);
    }

    /**
     * Sort the `$iterable` by value in descending order.
     *
     * Example:
     * ```php
     * Arr::sortDesc([2, 0, 1]);  // [2, 1, 0]
     * Arr::sortDesc(['a' => 1, 'b' => 2]);  // ['b' => 2, 'a' => 1]
     * ```
     *
     * @template TKey of array-key
     * @template TValue
     * @param iterable<TKey, TValue> $iterable Iterable to be traversed.
     * @param Closure(TValue, TKey): mixed|null $by
     * [Optional] User defined comparison callback.
     * The value returned will be used to sort the array.
     * @param int $flag
     * [Optional] Sort flag to change the behavior of the sort.
     * Defaults to `SORT_REGULAR`.
     * @param bool|null $reindex
     * [Optional] Result will be re-indexed if **true**.
     * If **null**, the result will be re-indexed only if it's a list.
     * Defaults to **null**.
     * @return array<TKey, TValue>
     */
    public static function sortDesc(
        iterable $iterable,
        ?Closure $by = null,
        int $flag = SORT_REGULAR,
        ?bool $reindex = null,
    ): array
    {
        return self::sort($iterable, SortOrder::Descending, $by, $flag, $reindex);
    }

    /**
     * Sorts the `$iterable` by value using the provided `$comparator` function.
     *
     * Example:
     * ```php
     * Arr::sortWith([1, 3, 2], fn($a, $b) => ($a < $b) ? -1 : 1); // [1, 2, 3]
     * ```
     *
     * @template TKey of array-key
     * @template TValue
     * @param iterable<TKey, TValue> $iterable
     * Iterable to be traversed.
     * @param Closure(TValue, TValue): int $comparator
     * The comparison function to use.
     * Utilize the spaceship operator (`<=>`) to easily compare two values.
     * @param bool|null $reindex
     * [Optional] Result will be re-indexed if **true**.
     * If **null**, the result will be re-indexed only if it's a list.
     * Defaults to **null**.
     * @return array<TKey, TValue>
     */
    public static function sortWith(
        iterable $iterable,
        Closure $comparator,
        ?bool $reindex = null,
    ): array
    {
        $copy = self::from($iterable);
        $reindex ??= array_is_list($copy);

        uasort($copy, $comparator);

        return $reindex
            ? array_values($copy)
            : $copy;
    }

    /**
     * Sorts the `$iterable` by key using the provided comparison function.
     *
     * Example:
     * ```php
     * $compare = fn($a, $b) => ($a < $b) ? -1 : 1;
     * Arr::sortWithKey([1 => 'a', 3 => 'b', 2 => 'c'], $compare); // [1 => 'a', 2 => 'c', 3 => 'b']
     * ```
     *
     * @template TKey of array-key
     * @template TValue
     * @param iterable<TKey, TValue> $iterable
     * Iterable to be traversed.
     * @param Closure(TKey, TKey): int $comparator
     * The comparison function to use.
     * Utilize the spaceship operator (`<=>`) to easily compare two values.
     * @return array<TKey, TValue>
     */
    public static function sortWithKey(
        iterable $iterable,
        Closure $comparator,
    ): array
    {
        $copy = self::from($iterable);
        uksort($copy, $comparator);
        return $copy;
    }

    /**
     * Splits the `$iterable` after the index where `$condition` returned **true**.
     *
     * Example:
     * ```php
     * Arr::splitAfter([1, 2, 3], fn($v) => $v === 1) // [[1], [2, 3]]
     * Arr::splitAfter([1, 2, 3], fn($v) => true) // [[1], [2], [3], []]
     * ```
     *
     * @template TKey of array-key
     * @template TValue
     * @param iterable<TKey, TValue> $iterable
     * Iterable to be traversed.
     * @param Closure(TValue, TKey): bool $condition
     * User defined condition callback. The callback must return a boolean value.
     * @param bool|null $reindex
     * [Optional] Result will be re-indexed if **true**.
     * If **null**, the result will be re-indexed only if it's a list.
     * Defaults to **null**.
     * @return list<array<TKey, TValue>>
     */
    public static function splitAfter(
        iterable $iterable,
        Closure $condition,
        ?bool $reindex = null,
    ): array
    {
        $array = self::from($iterable);
        $reindex ??= array_is_list($array);

        if (count($array) === 0) {
            return [];
        }

        $groups = [];
        $current = [];
        foreach ($array as $key => $val) {
            $reindex
                ? $current[] = $val
                : $current[$key] = $val;
            if (self::verifyBool($condition, $key, $val)) {
                $groups[] = $current;
                $current = [];
            }
        }
        $groups[] = $current;

        return $groups;
    }

    /**
     * Splits the `$iterable` after the given `$index`.
     *
     * Example:
     * ```php
     * Arr::splitBeforeIndex([1, 2, 3], 1) // [[1, 2], [3]]
     * Arr::splitBeforeIndex([1, 2, 3], -2) // [[1, 2], [3]]
     * Arr::splitBeforeIndex([1, 2, 3], 10) // [[1, 2, 3], []]
     * ```
     *
     * @template TKey of array-key
     * @template TValue
     * @param iterable<TKey, TValue> $iterable
     * Iterable to be traversed.
     * @param int $index
     * The index where the `$iterable` will be split starting from 0.
     * Negative index will count from the end.
     * @param bool|null $reindex
     * [Optional] Result will be re-indexed if **true**.
     * If **null**, the result will be re-indexed only if it's a list.
     * Defaults to **null**.
     * @return array{ 0: array<TKey, TValue>, 1: array<TKey, TValue> }
     */
    public static function splitAfterIndex(
        iterable $iterable,
        int $index,
        ?bool $reindex = null,
    ): array
    {
        $array = self::from($iterable);
        if ($index < 0) {
            $index = count($array) + $index;
        }
        ++$index;
        $i = 0;
        return self::partition(
            $array,
            static function() use (&$i, $index): bool {
                return $i++ < $index;
            },
            $reindex,
        );
    }

    /**
     * Splits the `$iterable` before the index where `$condition` returned **true**.
     *
     * Example:
     * ```php
     * Arr::splitBefore([1, 2, 3], fn($v) => $v === 2) // [[1], [2, 3]]
     * Arr::splitBefore([1, 2, 3], fn($v) => true) // [[], [1], [2], [3]]
     * ```
     *
     * @template TKey of array-key
     * @template TValue
     * @param iterable<TKey, TValue> $iterable
     * Iterable to be traversed.
     * @param Closure(TValue, TKey): bool $condition
     * User defined condition callback. The callback must return a boolean value.
     * @param bool|null $reindex
     * [Optional] Result will be re-indexed if **true**.
     * If **null**, the result will be re-indexed only if it's a list.
     * Defaults to **null**.
     * @return list<array<TKey, TValue>>
     */
    public static function splitBefore(
        iterable $iterable,
        Closure $condition,
        ?bool $reindex = null,
    ): array
    {
        $array = self::from($iterable);
        $reindex ??= array_is_list($array);

        if (count($array) === 0) {
            return [];
        }

        $groups = [];
        $current = [];
        foreach ($array as $key => $val) {
            if (self::verifyBool($condition, $key, $val)) {
                $groups[] = $current;
                $current = [];
            }
            $reindex
                ? $current[] = $val
                : $current[$key] = $val;
        }
        $groups[] = $current;
        return $groups;
    }

    /**
     * Splits the `$iterable` before the given `$index`.
     *
     * Example:
     * ```php
     * Arr::splitBeforeIndex([1, 2, 3], 2) // [[1, 2], [3]]
     * Arr::splitBeforeIndex([1, 2, 3], 10) // [[1, 2, 3], []]
     * ```
     *
     * @template TKey of array-key
     * @template TValue
     * @param iterable<TKey, TValue> $iterable
     * Iterable to be traversed.
     * @param int $index
     * The index where the `$iterable` will be split starting from 0.
     * Negative index will count from the end.
     * @param bool|null $reindex
     * [Optional] Result will be re-indexed if **true**.
     * If **null**, the result will be re-indexed only if it's a list.
     * Defaults to **null**.
     * @return array{ 0: array<TKey, TValue>, 1: array<TKey, TValue> }
     */
    public static function splitBeforeIndex(
        iterable $iterable,
        int $index,
        ?bool $reindex = null,
    ): array
    {
        $array = self::from($iterable);
        if ($index < 0) {
            $index = count($array) + $index;
        }
        $i = 0;
        return self::partition(
            $array,
            static function() use (&$i, $index): bool {
                return $i++ < $index;
            },
            $reindex,
        );
    }

    /**
     * Splits the `$iterable` into the given size.
     *
     * Example:
     * ```php
     * Arr::splitEvenly([1, 2, 3, 4, 5], 3); // [[1, 2], [3, 4], [5]]
     * Arr::splitEvenly([1, 2, 3], 1); // [[1, 2, 3]]
     * Arr::splitEvenly([], 2); // []
     * ```
     *
     * @template TKey of array-key
     * @template TValue
     * @param iterable<TKey, TValue> $iterable
     * Iterable to be traversed.
     * @param int $parts
     * Number of parts to split into.
     * @param bool|null $reindex
     * [Optional] Result will be re-indexed if **true**.
     * If **null**, the result will be re-indexed only if it's a list.
     * Defaults to **null**.
     * @return list<array<TKey, TValue>>
     */
    public static function splitEvenly(
        iterable $iterable,
        int $parts,
        ?bool $reindex = null,
    ): array
    {
        if ($parts <= 0) {
            throw new InvalidArgumentException("Expected: \$parts > 0. Got: {$parts}.", [
                'iterable' => $iterable,
                'parts' => $parts,
                'reindex' => $reindex,
            ]);
        }

        $array = self::from($iterable);
        $reindex ??= array_is_list($array);
        $total = count($array);
        $chunk = (int) ceil($total / $parts);

        $split = [];
        $current = [];
        $count = 0;
        foreach ($array as $key => $val) {
            $reindex
                ? $current[] = $val
                : $current[$key] = $val;
            ++$count;
            if ($count === $chunk) {
                $count = 0;
                $split[] = $current;
                $current = [];
            }
        }
        if (count($current) > 0) {
            $split[] = $current;
        }
        return $split;
    }

    /**
     * Returns **true** if `$iterable` starts with the given `$values`, **false** otherwise.
     *
     * Example:
     * ```php
     * Arr::startsWith([1, 2, 3], [1, 2]); // true
     * Arr::startsWith([1, 2, 3], [1, 3]); // false
     * Arr::startsWith([1, 2, 3], [1, 2, 3, 4]); // false
     * ```
     *
     * @template TKey of array-key
     * @template TValue
     * @param iterable<TKey, TValue> $iterable
     * @param iterable<int, TValue> $values
     * @return bool
     */
    public static function startsWith(
        iterable $iterable,
        iterable $values,
    ): bool
    {
        $values = self::values($values);
        $sizeOfValues = count($values);
        $index = 0;
        foreach ($iterable as $val) {
            if ($index === $sizeOfValues) {
                break;
            }
            if ($values[$index] !== $val) {
                return false;
            }
            ++$index;
        }
        return count($values) <= $index;
    }

    /**
     * Get the sum of the elements inside `$iterable`.
     * The elements must be af type int or float.
     * Returns `0` if empty.
     * Throws `InvalidElementException` if the iterable contains NAN.
     *
     * Example:
     * ```php
     * Arr::sum([1, 2, 3]); // 6
     * Arr::sum(['b' => 1, 'a' => 2]); // 3
     * Arr::sum([]) // 0
     * ```
     *
     * @template TKey of array-key
     * @template TValue of int|float
     * @param iterable<TKey, TValue> $iterable
     * Iterable to be traversed.
     * @return TValue
     */
    public static function sum(
        iterable $iterable,
    ): mixed
    {
        $total = 0;
        foreach ($iterable as $val) {
            $total += $val;
        }

        if (is_float($total) && is_nan($total)) {
            throw new InvalidElementException('$iterable cannot contain NAN.', [
                'iterable' => $iterable,
            ]);
        }

        /** @var TValue */
        return $total;
    }

    /**
     * Returns a copy of `$iterable` with the given keys swapped.
     * Throws `InvalidArgumentException` if the given key does not exist.
     *
     * Example:
     * ```php
     * Arr::swap([1, 2, 3], 0, 2); // [3, 2, 1]
     * ```
     *
     * @template TKey of array-key
     * @template TValue
     * @param iterable<TKey, TValue> $iterable
     * Iterable to be traversed.
     * @param TKey $key1
     * Key to be swapped.
     * @param TKey $key2
     * Key to be swapped.
     * @return array<TKey, TValue>
     */
    public static function swap(
        iterable $iterable,
        int|string $key1,
        int|string $key2,
        ?bool $reindex = null,
    ): array
    {
        $array = self::from($iterable);
        $reindex ??= array_is_list($array);

        if (!array_key_exists($key1, $array)) {
            throw new InvalidKeyException("Key: {$key1} does not exist.", [
                'iterable' => $iterable,
                'key1' => $key1,
                'key2' => $key2,
            ]);
        }

        if (!array_key_exists($key2, $array)) {
            throw new InvalidKeyException("Key: {$key2} does not exist.", [
                'iterable' => $iterable,
                'key1' => $key1,
                'key2' => $key2,
            ]);
        }

        if ($reindex) {
            [$array[$key1], $array[$key2]] = [$array[$key2], $array[$key1]];
            return $array;
        } else {
            $val1 = $array[$key1];
            $val2 = $array[$key2];
            $swapped = [];
            foreach ($array as $key => $val) {
                match ($key) {
                    $key1 => $swapped[$key2] = $val2,
                    $key2 => $swapped[$key1] = $val1,
                    default => $swapped[$key] = $val,
                };
            }
            return $swapped;
        }
    }

    /**
     * Returns the symmetric difference of the given iterables.
     * The given iterable must be of type list.
     * Throws `TypeMismatchException` if map is given.
     *
     * Example:
     * ```php
     * Arr::symDiff([1, 2], [2, 3]); // [1, 3]
     * Arr::symDiff([], [1]); // [1]
     * Arr::symDiff([1], []); // [1]
     * ```
     *
     * @template TValue
     * @param iterable<int, TValue> $iterable1
     * Iterable to be traversed.
     * @param iterable<int, TValue> $iterable2
     * Iterable to be traversed.
     * @param Closure(TValue, TValue): int<-1, 1>|null $by
     * [Optional] User defined comparison callback.
     * Return 1 if first argument is greater than the 2nd.
     * Return 0 if first argument is equal to the 2nd.
     * Return -1 if first argument is less than the 2nd.
     * Defaults to **null**.
     * @return list<TValue>
     */
    public static function symDiff(
        iterable $iterable1,
        iterable $iterable2,
        ?Closure $by = null,
    ): array
    {
        $array1 = self::from($iterable1);
        $array2 = self::from($iterable2);

        foreach ([$array1, $array2] as $i => $list) {
            if (!array_is_list($list)) {
                throw new TypeMismatchException('$iterable' . ($i+1) . ' must be a list, map given.', [
                    'iterable1' => $iterable1,
                    'iterable2' => $iterable2,
                    'by' => $by,
                ]);
            }
        }

        $by ??= Func::spaceship();

        $diff1 = array_values(array_udiff($array1, $array2, $by));
        $diff2 = array_values(array_udiff($array2, $array1, $by));

        return self::merge($diff1, $diff2);
    }

    /**
     * Take every `$nth` element from `$iterable`.
     *
     * @template TKey of array-key
     * @template TValue
     * @param iterable<TKey, TValue> $iterable
     * Iterable to be traversed.
     * @param int $nth
     * Nth value to take. Must be >= 1.
     * @return array<TKey, TValue>
     */
    public static function takeEvery(
        iterable $iterable,
        int $nth,
        ?bool $reindex = null,
    ): array
    {
        if ($nth <= 0) {
            throw new InvalidArgumentException("Expected: \$nth >= 1. Got: {$nth}.", [
                'iterable' => $iterable,
                'nth' => $nth,
                'reindex' => $reindex,
            ]);
        }

        $i = 0;
        return self::takeIf($iterable, static function() use (&$i, $nth) {
            ++$i;
            return $i % $nth === 0;
        }, $reindex);
    }

    /**
     * Take the first n elements from `$iterable`.
     *
     * Example:
     * ```php
     * Arr::takeFirst([2, 3, 4], 2); // [2, 3]
     * Arr::takeFirst(['a' => 1, 'b' => 2], 1); // ['a' => 1]
     * ```
     *
     * @template TKey of array-key
     * @template TValue
     * @param iterable<TKey, TValue> $iterable
     * Iterable to be traversed.
     * @param int $amount
     * Amount of elements to take. Must be >= 0.
     * @return array<TKey, TValue>
     */
    public static function takeFirst(
        iterable $iterable,
        int $amount,
    ): array
    {
        return iterator_to_array(Iter::takeFirst($iterable, $amount));
    }

    /**
     * Iterates over each element in iterable and passes them to the callback function.
     * If the callback function returns **true** the element is passed on to the new array.
     *
     * Example:
     * ```php
     * Arr::takeIf([null, '', 1], fn($v) => $v === ''); // [null, 1]
     * Arr::takeIf([null, '', 0], Str::isNotBlank(...)); // [0]
     * Arr::takeIf(['a' => true, 'b' => 1], fn($v) => $v === 1); // ['b' => 1]
     * ```
     *
     * @template TKey of array-key
     * @template TValue
     * @param iterable<TKey, TValue> $iterable
     * Iterable to be traversed.
     * @param Closure(TValue, TKey): bool $condition
     * User defined condition callback. The callback must return a boolean value.
     * @param bool|null $reindex
     * [Optional] Result will be re-indexed if **true**.
     * If **null**, the result will be re-indexed only if it's a list.
     * Defaults to **null**.
     * @return array<TKey, TValue>
     */
    public static function takeIf(
        iterable $iterable,
        Closure $condition,
        ?bool $reindex = null,
    ): array
    {
        $array = self::from($iterable);
        $reindex ??= array_is_list($array);
        return iterator_to_array(Iter::takeIf($array, $condition, $reindex));
    }

    /**
     * Returns a new array which only contain the elements that has matching
     * keys in `$iterable`. Non-existent keys will be ignored.
     * If `$safe` is set to **true**, `MissingKeyException` will be thrown
     * if a key does not exist in `$iterable`.
     *
     * Example:
     * ```php
     * Arr::takeKeys(['a' => 1, 'b' => 2, 'c' => 3], ['b', 'd']); // ['b' => 2]
     * Arr::takeKeys([1, 2, 3], [1]); // [2]
     * ```
     *
     * @template TKey of int|string
     * @template TValue
     * @param iterable<TKey, TValue> $iterable
     * Iterable to be traversed.
     * @param iterable<int, TKey> $keys
     * Keys to be included.
     * @param bool $safe
     * [Optional] If this is set to **true**, `MissingKeyException` will be
     * thrown if key does not exist in `$iterable`.
     * If set to **false**, non-existing keys will be filled with **null**.
     * Defaults to **true**.
     * @param bool|null $reindex
     * [Optional] Result will be re-indexed if **true**.
     * If **null**, the result will be re-indexed only if it's a list.
     * Defaults to **null**.
     * @return array<TKey, TValue>
     */
    public static function takeKeys(
        iterable $iterable,
        iterable $keys,
        bool $safe = true,
        ?bool $reindex = null,
    ): array
    {
        $array = self::from($iterable);
        $reindex ??= array_is_list($array);

        $missingKeys = [];
        $result = [];
        foreach ($keys as $key) {
            if (array_key_exists($key, $array)) {
                $reindex
                    ? $result[] = $array[$key]
                    : $result[$key] = $array[$key];
            } elseif ($safe) {
                $missingKeys[] = $key;
            }
        }

        if ($safe && self::isNotEmpty($missingKeys)) {
            throw new MissingKeyException($missingKeys, [
                'iterable' => $iterable,
                'givenKeys' => $keys,
                'missingKeys' => $missingKeys,
            ]);
        }

        return $result;
    }

    /**
     * Take the last n elements from `$iterable`.
     *
     * Example:
     * ```php
     * Arr::takeLast([2, 3, 4], 2); // [3, 4]
     * Arr::takeLast(['a' => 1, 'b' => 2], 1); // ['b' => 2]
     * ```
     *
     * @template TKey of array-key
     * @template TValue
     * @param iterable<TKey, TValue> $iterable
     * Iterable to be traversed.
     * @param int $amount
     * Amount of items to be dropped from the end. Must be >= 0.
     * @param bool|null $reindex
     * [Optional] Result will be re-indexed if **true**.
     * If **null**, the result will be re-indexed only if it's a list.
     * Defaults to **null**.
     * @return array<TKey, TValue>
     */
    public static function takeLast(
        iterable $iterable,
        int $amount,
        ?bool $reindex = null,
    ): array
    {
        if ($amount < 0) {
            throw new InvalidArgumentException("Expected: \$amount >= 0. Got: {$amount}.", [
                'iterable' => $iterable,
                'amount' => $amount,
            ]);
        }

        $array = self::from($iterable);
        $length = count($array);
        $reindex ??= array_is_list($array);
        $offset = max(0, $length - $amount);
        return iterator_to_array(Iter::slice($array, $offset, PHP_INT_MAX, $reindex));
    }

    /**
     * Takes elements in `$iterable` until `$condition` returns **true**.
     *
     * Example:
     * ```php
     * Arr::takeUntil([1, 1, 3, 2], fn($v) => $v > 2); // [1, 1]
     * Arr::takeUntil(['b' => 1, 'a' => 3], fn($v) => $v > 2); // ['b' => 1]
     * ```
     *
     * @template TKey of array-key
     * @template TValue
     * @param iterable<TKey, TValue> $iterable
     * Iterable to be traversed.
     * @param Closure(TValue, TKey): bool $condition
     * A break condition callback that should return false when loop should stop.
     * @return array<TKey, TValue>
     */
    public static function takeUntil(
        iterable $iterable,
        Closure $condition,
    ): array
    {
        return iterator_to_array(Iter::takeUntil($iterable, $condition));
    }

    /**
     * Takes elements in `$iterable` while `$condition` returns **true**.
     *
     * Example:
     * ```php
     * Arr::takeWhile([1, 1, 3, 2], fn($v) => $v <= 2); // [1, 1]
     * Arr::takeWhile(['a' => 1, 'b' => 4], fn($v) => $v < 4); // ['a' => 1]
     * ```
     *
     * @template TKey of array-key
     * @template TValue
     * @param iterable<TKey, TValue> $iterable
     * Iterable to be traversed.
     * @param Closure(TValue, TKey): bool $condition
     * User defined condition callback. The callback must return a boolean value.
     * @return array<TKey, TValue>
     */
    public static function takeWhile(
        iterable $iterable,
        Closure $condition,
    ): array
    {
        return iterator_to_array(Iter::takeWhile($iterable, $condition));
    }

    /**
     * Generates URL encoded query string.
     * Encoding follows RFC3986 (spaces will be converted to `%20`).
     *
     * Example:
     * ```php
     * Arr::toUrlQuery(['a' => 1, 'b' => 2]); // "a=1&b=2"
     * Arr::toUrlQuery(['a' => 1], 't'); // t%5Ba%5D=1 (decoded: t[a]=1)
     * ```
     *
     * @template TKey of array-key
     * @template TValue
     * @param iterable<TKey, TValue> $iterable
     * Iterable to be traversed.
     * @param string|null $namespace
     * [Optional] Adds namespace to wrap the iterable.
     * Defaults to **null**.
     * @return string
     */
    public static function toUrlQuery(
        iterable $iterable,
        ?string $namespace = null,
    ): string
    {
        $array = self::from($iterable);
        $data = $namespace !== null ? [$namespace => $array] : $array;
        return http_build_query($data, '', '&', PHP_QUERY_RFC3986);
    }

    /**
     * Removes duplicate values from `$iterable` and returns it as an array.
     *
     * This differs from `array_unique` in that, this does not do a
     * string conversion before comparing.
     * For example, `array_unique([1, true])` will result in: `[1]` but
     * doing `Arr::unique([1, true])` will result in: `[1, true]`.
     *
     * Example:
     * ```php
     * Arr::unique([1, 1, null, 0, '']); // [1, null, 0, '']
     * Arr::unique([1, 2, 3, 4], fn($v) => $v % 2); // [1, 2]
     * ```
     *
     * @template TKey of array-key
     * @template TValue
     * @param iterable<TKey, TValue> $iterable
     * Iterable to be traversed.
     * @param Closure(TValue, TKey): mixed|null $by
     * [Optional] Called for every element in `$iterable`.
     * Returned value will be used to check for duplicates.
     * [Optional] Defaults to **null**.
     * @param bool|null $reindex
     * [Optional] Result will be re-indexed if **true**.
     * If **null**, the result will be re-indexed only if it's a list.
     * Defaults to **null**.
     * @return array<TKey, TValue>
     */
    public static function unique(
        iterable $iterable,
        ?Closure $by = null,
        ?bool $reindex = null,
    ): array
    {
        $by ??= static fn(mixed $val, int|string $key) => $val;

        $array = self::from($iterable);
        $reindex ??= array_is_list($array);

        $refs = [];
        $preserved = [];

        foreach ($array as $key => $val) {
            $ref = self::valueToKeyString($by($val, $key));
            if (!array_key_exists($ref, $refs)) {
                $refs[$ref] = null;
                $reindex
                    ? $preserved[] = $val
                    : $preserved[$key] = $val;
            }
        }
        return $preserved;
    }

    /**
     * Converts `$iterable` to a list. Any keys will be dropped.
     *
     * Example:
     * ```php
     * Arr::values(['a' => 1, 'b' => 2]) // [1, 2]
     * Arr::values([1 => 1, 0 => 2]) // [1, 2]
     * ```
     *
     * @template TKey of array-key
     * @template TValue
     * @param iterable<TKey, TValue> $iterable
     * Iterable to be traversed.
     * @return list<TValue>
     */
    public static function values(
        iterable $iterable,
    ): array
    {
        $values = [];
        foreach ($iterable as $val) {
            $values[] = $val;
        }
        return $values;
    }

    /**
     * Returns a copy of `$iterable` as array with the specified `$defaults` merged in
     * if the corresponding key does not exist `$iterable`.
     *
     * @template TKey of array-key
     * @template TValue
     * @param iterable<TKey, TValue> $iterable
     * Iterable to be checked for missing elements.
     * @param iterable<TKey, TValue> $defaults
     * Iterable to be set as default.
     * @return array<TKey, TValue>
     */
    public static function withDefaults(
        iterable $iterable,
        iterable $defaults,
    ): array
    {
        return self::from($iterable) + self::from($defaults);
    }

    /**
     * Returns a copy of `$iterable` as array without the specified `$value` excluded.
     *
     * Example:
     * ```php
     * Arr::values(['a' => 1, 'b' => 2]) // [1, 2]
     * Arr::values([1 => 1, 0 => 2]) // [1, 2]
     * ```
     *
     * @template TKey of array-key
     * @template TValue
     * @param iterable<TKey, TValue> $iterable
     * Iterable to be traversed.
     * @param TValue $value
     * Value to be excluded.
     * @param bool|null $reindex
     * [Optional] Result will be re-indexed if **true**.
     * If **null**, the result will be re-indexed only if it's a list.
     * @return array<TKey, TValue>
     */
    public static function without(
        iterable $iterable,
        mixed $value,
        ?bool $reindex = null,
    ): array
    {
        return self::filter($iterable, Func::notSame($value), $reindex);
    }

    /**
     * Returns a list consisting of sub lists where each sub array is an aggregate of
     * elements in `$iterables` at each position. The given `$iterables` must all be
     * a list.
     *
     * Example:
     * ```php
     * Arr::zip([1, 2], [3, 4], [5, 6]); // [[1, 3, 5], [2, 4, 6]]
     * Arr::zip([1, 2], [3]); // [[1, 3], [2, null]]
     * Arr::zip([1], [2, 3]); // [[1, 2]]
     * ```
     *
     * @template TValue
     * @param iterable<int, TValue> $iterables
     * Iterables to be zipped.
     * @return list<array<int, TValue|null>>
     */
    public static function zip(
        iterable ...$iterables,
    ): array
    {
        if (count($iterables) < 1) {
            throw new InvalidArgumentException('Arr::zip() expects at least 1 argument.', [
                'iterables' => $iterables,
            ]);
        }

        $grouped = [];
        foreach ($iterables as $iterable) {
            $array = self::from($iterable);
            $grouped[] = $array;
            if (!array_is_list($array)) {
                $position = count($grouped);
                throw new TypeMismatchException("Argument #{$position} must be a list, map given.", [
                    'iterables' => $iterables,
                    'position' => $position,
                ]);
            }
        }

        $firstList = array_shift($grouped);
        $listCount = count($grouped);
        $array = [];
        foreach ($firstList as $i => $val) {
            $each = [$val];
            for ($j = 0; $j < $listCount; $j++) {
                $each[] = $grouped[$j][$i] ?? null;
            }
            $array[] = $each;
        }
        return $array;
    }

    /**
     * Ensure that a given key is an int or string and return the key.
     *
     * @param mixed $key
     * @return array-key
     */
    private static function ensureKeyType(
        mixed $key,
    ): int|string
    {
        if (is_int($key) || is_string($key)) {
            return $key;
        }

        $type = gettype($key);
        throw new InvalidKeyException("Expected: key of type int|string. {$type} given.", [
            'key' => $key,
        ]);
    }

    /**
     * Converts value into an identifiable string.
     * Used for checking for duplicates.
     *
     * @see self::duplicates()
     * @see self::unique()
     *
     * @param mixed $val
     * @return string
     */
    private static function valueToKeyString(
        mixed $val,
    ): string
    {
        try {
            return match (true) {
                is_null($val) => '',
                is_int($val) => "i:$val",
                is_float($val) => "f:$val",
                is_bool($val) => "b:$val",
                is_string($val) => "s:$val",
                is_array($val) => 'a:' . json_encode(array_map(self::valueToKeyString(...), $val), JSON_THROW_ON_ERROR),
                is_object($val) => 'o:' . spl_object_id($val),
                is_resource($val) => 'r:' . get_resource_id($val),
                default => throw new UnreachableException('Invalid Type: ' . gettype($val) . '.', [
                    'value' => $val,
                ]),
            };
            // @codeCoverageIgnoreStart
        } catch (JsonException $e) {
            throw new UnreachableException(
                message: 'json_encode should never throw an error here but it did.',
                context: ['value' => $val],
                previous: $e,
            );
        }
        // @codeCoverageIgnoreEnd
    }

    /**
     * Runs the given condition with `$val` and `$key` as the argument.
     *
     * @template TKey of array-key
     * @template TValue
     * @param Closure(TValue, TKey): bool $condition
     * User defined condition callback. The callback must return a boolean value.
     * @param TKey $key
     * Key to pass on to the given condition.
     * @param TValue $val
     * Value to pass on to the given condition.
     * @return bool
     */
    private static function verifyBool(
        Closure $condition,
        mixed $key,
        mixed $val,
    ): bool
    {
        return $condition($val, $key);
    }

    /**
     * Set the default randomizer for the following methods.
     *
     * @see self::sample()
     * @see self::sampleMany()
     * @see self::shuffle()
     *
     * @param Randomizer|null $randomizer
     * @return void
     */
    public static function setDefaultRandomizer(
        ?Randomizer $randomizer,
    ): void
    {
        self::$defaultRandomizer = $randomizer;
    }

    /**
     * Get the default randomizer used in this class.
     *
     * @return Randomizer
     */
    public static function getDefaultRandomizer(): Randomizer
    {
        return self::$defaultRandomizer ??= new Randomizer();
    }

    /**
     * @param array<array-key, mixed> $array1
     * @param array<array-key, mixed> $array2
     * @return bool
     */
    private static function isDifferentArrayType(array $array1, array $array2): bool
    {
        return array_is_list($array1) !== array_is_list($array2)
            && $array1 !== self::EMPTY
            && $array2 !== self::EMPTY;
    }

    /**
     * @param array<array-key, mixed> $array
     * @return string
     */
    private static function getArrayType(array $array): string
    {
        return array_is_list($array) ? 'list' : 'map';
    }

    /**
     * A dummy instance used to check for miss in methods below.
     *
     * @return self
     * @see atOrNull
     * @see firstOrNull
     * @see getOrNull
     * @see lastOrNull
     * @see pullOrNull
     */
    private static function miss(): self
    {
        static $miss = new self();
        return $miss;
    }
}
