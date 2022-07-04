<?php declare(strict_types=1);

namespace Kirameki\Utils;

use Closure;
use Iterator;
use Webmozart\Assert\Assert;
use function count;
use function is_iterable;

class Iter
{
    /**
     *  Chunk iterable into given size and pass it as iterator value.
     *
     * @template TKey of array-key
     * @template TValue
     * @param iterable<TKey, TValue> $iterable
     * Iterable to be traversed.
     * @param int $size
     * Size of each chunk. Must be >= 1.
     * @param bool $reindex
     * If set to **true** the array will be reindexed.
     * @return Iterator<int, array<TKey, TValue>>
     */
    public static function chunk(iterable $iterable, int $size, bool $reindex = false): Iterator
    {
        Assert::positiveInteger($size);

        $remaining = $size;
        $chunk = [];
        foreach ($iterable as $key => $val) {
            $reindex
                ? $chunk[] = $val
                : $chunk[$key] = $val;

            if (--$remaining === 0) {
                yield $chunk;
                $remaining = $size;
                $chunk = [];
            }
        }

        if (count($chunk) > 0) {
            yield $chunk;
        }
    }

    /**
     * Iterate through the iterable with all **null** values ignored.
     *
     * @template TKey of array-key
     * @template TValue
     * @param iterable<TKey, TValue> $iterable
     * Iterable to be traversed.
     * @param bool $reindex
     * If set to **true** the array will be reindexed.
     * @return Iterator<TKey, TValue>
     */
    public static function compact(iterable $iterable, bool $reindex = false): Iterator
    {
        foreach ($iterable as $key => $val) {
            if ($val !== null) {
                if ($reindex) {
                    yield $val;
                } else {
                    yield $key => $val;
                }
            }
        }
    }

    /**
     * Create an iterator which iterates after the given amount.
     *
     * @template TKey of array-key
     * @template TValue
     * @param iterable<TKey, TValue> $iterable
     * Iterable to be traversed.
     * @param int $amount
     * Amount of elements to drop. Must be >= 0.
     * @param bool $reindex
     * If set to **true** the array will be reindexed.
     * @return Iterator<TKey, TValue>
     */
    public static function drop(iterable $iterable, int $amount, bool $reindex = false): Iterator
    {
        Assert::greaterThanEq($amount, 0);
        return static::slice($iterable, $amount, PHP_INT_MAX, $reindex);
    }

    /**
     * Create an iterator which iterates and drop values until the condition returns **true**.
     *
     * @template TKey of array-key
     * @template TValue
     * @param iterable<TKey, TValue> $iterable
     * Iterable to be traversed.
     * @param Closure(TValue, TKey): bool $condition
     * A condition that should return a boolean.
     * @param bool $reindex
     * If set to **true** the array will be reindexed.
     * @return Iterator<TKey, TValue>
     */
    public static function dropUntil(iterable $iterable, Closure $condition, bool $reindex = false): Iterator
    {
        $drop = true;
        foreach ($iterable as $key => $item) {
            if ($drop && static::verify($condition, $key, $item)) {
                $drop = false;
            }

            if (!$drop) {
                if ($reindex) {
                    yield $item;
                } else {
                    yield $key => $item;
                }
            }
        }
    }

    /**
     * Create an iterator which iterates and drop values while the condition returns **true**.
     *
     * @template TKey of array-key
     * @template TValue
     * @param iterable<TKey, TValue> $iterable
     * Iterable to be traversed.
     * @param Closure(TValue, TKey): bool $condition
     * A condition that should return a boolean.
     * @param bool $reindex
     * If set to **true** the array will be reindexed.
     * @return Iterator<TKey, TValue>
     */
    public static function dropWhile(iterable $iterable, Closure $condition, bool $reindex = false): Iterator
    {
        $drop = true;
        foreach ($iterable as $key => $item) {
            if ($drop && !static::verify($condition, $key, $item)) {
                $drop = false;
            }

            if (!$drop) {
                if ($reindex) {
                    yield $item;
                } else {
                    yield $key => $item;
                }
            }
        }
    }

    /**
     * Create an iterator that will send the key/value to the generator if the condition is **true**.
     *
     * @template TKey of array-key
     * @template TValue
     * @param iterable<TKey, TValue> $iterable
     * Iterable to be traversed.
     * @param Closure(TValue, TKey): bool $condition
     * A condition that should return a boolean.
     * @param bool $reindex
     * If set to **true** the array will be reindexed.
     * @return Iterator<TKey, TValue>
     */
    public static function filter(iterable $iterable, Closure $condition, bool $reindex = false): Iterator
    {
        foreach ($iterable as $key => $val) {
            if (static::verify($condition, $key, $val)) {
                if ($reindex) {
                    yield $val;
                } else {
                    yield $key => $val;
                }
            }
        }
    }

    /**
     * Create an iterator that will map and also flatten the result of the callback.
     *
     * @template TKey of array-key
     * @template TValue
     * @param iterable<TKey, TValue> $iterable
     * Iterable to be traversed.
     * @param Closure(TValue, TKey): mixed $callback
     * Closure that will be called for each key/value. The returned value will be yielded.
     * @return Iterator<int, mixed>
     */
    public static function flatMap(iterable $iterable, Closure $callback): Iterator
    {
        foreach ($iterable as $key => $val) {
            $result = $callback($val, $key);
            if (is_iterable($result)) {
                foreach ($result as $each) {
                    yield $each;
                }
            } else {
                yield $result;
            }
        }
    }

    /**
     * Create an iterator that will flatten any iterable value.
     *
     * @template TKey of array-key
     * @param iterable<TKey, mixed> $iterable
     * Iterable to be traversed.
     * @param int $depth
     * Depth must be >= 1. Default: 1.
     * @return Iterator<mixed, mixed>
     */
    public static function flatten(iterable $iterable, int $depth = 1): Iterator
    {
        Assert::positiveInteger($depth);
        return static::flattenImpl($iterable, $depth);
    }

    /**
     * Actual implementation for flatten.
     *
     * @template TKey of array-key
     * @param iterable<TKey, mixed> $iterable
     * Iterable to be traversed.
     * @param int $depth
     * Depth must be >= 1. Default: 1.
     * @return Iterator<mixed, mixed>
     */
    protected static function flattenImpl(iterable $iterable, int $depth = 1): Iterator
    {
        foreach ($iterable as $key => $val) {
            if (is_iterable($val) && $depth > 0) {
                foreach (static::flattenImpl($val, $depth - 1) as $_key => $_val) {
                    yield $_key => $_val;
                }
            } else {
                yield $key => $val;
            }
        }
    }

    /**
     * Create an iterator that will send key as value and value as key to the generator.
     *
     * @template TKey of array-key
     * @template TValue
     * @param iterable<TKey, TValue> $iterable Iterable to be traversed.
     * @return Iterator<TValue, TKey>
     */
    public static function flip(iterable $iterable): Iterator
    {
        foreach ($iterable as $key => $val) {
            Assert::validArrayKey($key);
            yield $val => $key;
        }
    }

    /**
     * Create an iterator that will send the key to the generator as value.
     *
     * @template TKey of array-key
     * @param iterable<TKey, mixed> $iterable
     * Iterable to be traversed.
     * @return Iterator<int, TKey>
     */
    public static function keys(iterable $iterable): Iterator
    {
        foreach ($iterable as $key => $item) {
            yield $key;
        }
    }

    /**
     * Create an iterator that will send the result of the closure as value to the generator.
     *
     * @template TKey of array-key
     * @template TValue
     * @template TMapValue
     * @param iterable<TKey, TValue> $iterable
     * Iterable to be traversed.
     * @param Closure(TValue, TKey): TMapValue $callback
     * Closure which the result will be mapped as value.
     * @return Iterator<TKey, TMapValue>
     */
    public static function map(iterable $iterable, Closure $callback): Iterator
    {
        foreach ($iterable as $key => $val) {
            yield $key => $callback($val, $key);
        }
    }

    /**
     * Create an iterator that will repeat through the iterable for a given amount of times.
     *
     * @template T
     * @param iterable<array-key, T> $iterable
     * Iterable to be traversed.
     * @param int<0, max> $times
     * Amount of times the iterable will be repeated.
     * @return Iterator<int, T>
     */
    public static function repeat(iterable $iterable, int $times): Iterator
    {
        Assert::greaterThanEq($times, 0);

        for ($i = 0; $i < $times; $i++) {
            foreach ($iterable as $val) {
                yield $val;
            }
        }
    }

    /**
     * Create an iterator that will iterate starting from the offset up to the given length.
     *
     * @template TKey of array-key
     * @template TValue
     * @param iterable<TKey, TValue> $iterable
     * Iterable to be traversed.
     * @param int $offset
     * If offset is non-negative, the sequence will start at that offset.
     * If offset is negative, the sequence will start that far from the end.
     * @param int $length
     * If length is given and is positive, then the sequence will have up to that many elements in it.
     * If the iterable is shorter than the length, then only the available array elements will be present.
     * If length is given and is negative then the sequence will stop that many elements from the end.
     * If it is omitted, then the sequence will have everything from offset up until the end.
     * @param bool $reindex
     * If set to **true** the array will be reindexed.
     * @return Iterator<TKey, TValue>
     */
    public static function slice(iterable $iterable, int $offset, int $length = PHP_INT_MAX, bool $reindex = false): Iterator
    {
        $isNegativeOffset = $offset < 0;
        $isNegativeLength = $length < 0;

        if ($isNegativeOffset || $isNegativeLength) {
            $count = 0;
            foreach ($iterable as $ignored) {
                ++$count;
            }
            if ($isNegativeOffset) {
                $offset = $count + $offset;
            }
            if ($isNegativeLength) {
                $length = $count + $length;
            }
        }

        $i = 0;
        foreach ($iterable as $key => $val) {
            if ($i++ < $offset) {
                continue;
            }

            if ($i > $offset + $length) {
                break;
            }

            if ($reindex) {
                yield $val;
            } else {
                yield $key => $val;
            }
        }
    }

    /**
     * Create an iterator which takes the given amount of values.
     *
     * @template TKey of array-key
     * @template TValue
     * @param iterable<TKey, TValue> $iterable
     * $iterable Iterable to be traversed.
     * @param int $amount
     * Amount of elements to take. Must be >= 0.
     * @return Iterator<TKey, TValue>
     */
    public static function take(iterable $iterable, int $amount): Iterator
    {
        Assert::greaterThanEq($amount, 0);
        return static::slice($iterable, 0, $amount);
    }

    /**
     * Create an iterator which iterates and sends key/value to the generator until the condition returns **true**.
     *
     * @template TKey of array-key
     * @template TValue
     * @param iterable<TKey, TValue> $iterable
     * Iterable to be traversed.
     * @param Closure(TValue, TKey): bool $condition
     * @return Iterator<TKey, TValue>
     */
    public static function takeUntil(iterable $iterable, Closure $condition): Iterator
    {
        foreach ($iterable as $key => $item) {
            if (!static::verify($condition, $key, $item)) {
                yield $key => $item;
            } else {
                break;
            }
        }
    }

    /**
     * Create an iterator which iterates and sends key/value to the generator while the condition returns **true**.
     *
     * @template TKey of array-key
     * @template TValue
     * @param iterable<TKey, TValue> $iterable
     * Iterable to be traversed.
     * @param Closure(TValue, TKey): bool $condition
     * @return Iterator<TKey, TValue>
     */
    public static function takeWhile(iterable $iterable, Closure $condition): Iterator
    {
        foreach ($iterable as $key => $item) {
            if (static::verify($condition, $key, $item)) {
                yield $key => $item;
            } else {
                break;
            }
        }
    }

    /**
     * Create an iterator that will send only the value to the generator.
     *
     * @template TKey of array-key
     * @template TValue
     * @param iterable<TKey, TValue> $iterable
     * Iterable to be traversed.
     * @return Iterator<int, TValue>
     */
    public static function values(iterable $iterable): Iterator
    {
        foreach ($iterable as $val) {
            yield $val;
        }
    }

    /**
     * Invoke the condition closure and make sure that it returns a boolean value.
     *
     * @template TKey of array-key
     * @template TValue
     * @param Closure(TValue, TKey): bool $condition
     * The condition that returns a boolean value.
     * @param TKey $key
     * the 2nd argument for the condition closure.
     * @param TValue $val
     * 1st argument for the condition closure.
     * @return bool
     */
    protected static function verify(Closure $condition, mixed $key, mixed $val): bool
    {
        return $condition($val, $key);
    }
}
