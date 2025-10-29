<?php declare(strict_types=1);

namespace Kirameki\Collections\Utils;

use Closure;
use Generator;
use Kirameki\Core\Exceptions\InvalidArgumentException;
use function array_slice;
use function count;
use function is_iterable;
use const PHP_INT_MAX;

final class Iter
{
    /**
     * Creates a Generator which chunks elements into given size and passes it to the Generator.
     *
     * @template TKey of array-key
     * @template TValue
     * @param iterable<TKey, TValue> $iterable
     * Iterable to be traversed.
     * @param int $size
     * Size of each chunk. Must be >= 1.
     * @param bool $reindex
     * If set to **true** the array will be re-indexed.
     * @return Generator<int, array<TKey, TValue>>
     */
    public static function chunk(
        iterable $iterable,
        int $size,
        bool $reindex = false,
    ): Generator
    {
        if ($size < 1) {
            throw new InvalidArgumentException("Expected: \$size >= 1. Got: {$size}.", [
                'iterable' => $iterable,
                'size' => $size,
            ]);
        }

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
     * Creates a Generator that will send the result of the Generator returned by the `$callback`.
     *
     * @template TKey of array-key
     * @template TValue
     * @template TMapValue
     * @param iterable<TKey, TValue> $iterable
     * Iterable to be traversed.
     * @param Closure(TValue, TKey): Generator<int, TMapValue> $callback
     * Closure that will be called for each key/value. The returned value will be yielded.
     * @return Generator<int, TMapValue>
     */
    public static function collect(
        iterable $iterable,
        Closure $callback,
    ): Generator
    {
        foreach ($iterable as $key => $val) {
            foreach (self::verifyIterable($callback, $key, $val) as $v) {
                yield $v;
            }
        }
    }

    /**
     * Creates a Generator which iterates after the given amount.
     *
     * @template TKey of array-key
     * @template TValue
     * @param iterable<TKey, TValue> $iterable
     * Iterable to be traversed.
     * @param int $amount
     * Amount of elements to drop from the front. Must be >= 0.
     * @param bool $reindex
     * If set to **true** the array will be re-indexed.
     * @return Generator<TKey, TValue>
     */
    public static function dropFirst(
        iterable $iterable,
        int $amount,
        bool $reindex = false,
    ): Generator
    {
        if ($amount < 0) {
            throw new InvalidArgumentException("Expected: \$amount >= 0. Got: {$amount}.", [
                'iterable' => $iterable,
                'amount' => $amount,
            ]);
        }

        return self::slice($iterable, $amount, PHP_INT_MAX, $reindex);
    }

    /**
     * Creates a Generator that will send the key/value to the generator if the condition is **false**.
     *
     * @template TKey of array-key
     * @template TValue
     * @param iterable<TKey, TValue> $iterable
     * Iterable to be traversed.
     * @param Closure(TValue, TKey): bool $condition
     * A condition that should return a boolean.
     * @param bool $reindex
     * If set to **true** the array will be re-indexed.
     * @return Generator<TKey, TValue>
     */
    public static function dropIf(
        iterable $iterable,
        Closure $condition,
        bool $reindex = false,
    ): Generator
    {
        foreach ($iterable as $key => $val) {
            if (!self::verifyBool($condition, $key, $val)) {
                if ($reindex) {
                    // @phpstan-ignore generator.keyType
                    yield $val;
                } else {
                    yield $key => $val;
                }
            }
        }
    }

    /**
     * Creates a Generator which iterates and drop values until the condition returns **true**.
     *
     * @template TKey of array-key
     * @template TValue
     * @param iterable<TKey, TValue> $iterable
     * Iterable to be traversed.
     * @param Closure(TValue, TKey): bool $condition
     * A condition that should return a boolean value.
     * @param bool $reindex
     * If set to **true** the array will be re-indexed.
     * @return Generator<TKey, TValue>
     */
    public static function dropUntil(
        iterable $iterable,
        Closure $condition,
        bool $reindex = false,
    ): Generator
    {
        $drop = true;
        foreach ($iterable as $key => $item) {
            if ($drop && self::verifyBool($condition, $key, $item)) {
                $drop = false;
            }

            if (!$drop) {
                if ($reindex) {
                    // @phpstan-ignore generator.keyType
                    yield $item;
                } else {
                    yield $key => $item;
                }
            }
        }
    }

    /**
     * Creates a Generator which iterates and drop values while the condition returns **true**.
     *
     * @template TKey of array-key
     * @template TValue
     * @param iterable<TKey, TValue> $iterable
     * Iterable to be traversed.
     * @param Closure(TValue, TKey): bool $condition
     * A condition that should return a boolean.
     * @param bool $reindex
     * If set to **true** the array will be re-indexed.
     * @return Generator<TKey, TValue>
     */
    public static function dropWhile(
        iterable $iterable,
        Closure $condition,
        bool $reindex = false,
    ): Generator
    {
        $drop = true;
        foreach ($iterable as $key => $item) {
            if ($drop && !self::verifyBool($condition, $key, $item)) {
                $drop = false;
            }

            if (!$drop) {
                if ($reindex) {
                    // @phpstan-ignore generator.keyType
                    yield $item;
                } else {
                    yield $key => $item;
                }
            }
        }
    }

    /**
     * Iterates through `$iterable` and invoke `$callback` for each element.
     *
     * @template TKey of array-key
     * @template TValue
     * @param iterable<TKey, TValue> $iterable
     * Iterable to be traversed.
     * @param Closure(TValue, TKey): (mixed|void) $callback
     * Callback which is called for every element of `$iterable`.
     * @return Generator<TKey, TValue>
     */
    public static function each(
        iterable $iterable,
        Closure $callback,
    ): Generator
    {
        foreach ($iterable as $key => $item) {
            $callback($item, $key);
            yield $key => $item;
        }
    }

    /**
     * Creates a Generator that will map and also flatten the result of the callback.
     *
     * @template TKey of array-key
     * @template TValue
     * @template TMapValue
     * @param iterable<TKey, TValue> $iterable
     * Iterable to be traversed.
     * @param Closure(TValue, TKey): iterable<int, TMapValue> $callback
     * Closure that will be called for each key/value. The returned value will be yielded.
     * @return Generator<int, TMapValue>
     */
    public static function flatMap(
        iterable $iterable,
        Closure $callback,
    ): Generator
    {
        foreach ($iterable as $key => $val) {
            foreach (self::verifyIterable($callback, $key, $val) as $each) {
                yield $each;
            }
        }
    }

    /**
     * Creates a Generator that will flatten any iterable value.
     *
     * @template TKey of array-key
     * @param iterable<TKey, mixed> $iterable
     * Iterable to be traversed.
     * @param int $depth
     * Depth must be >= 1. Default: 1.
     * @return Generator<mixed, mixed>
     */
    public static function flatten(
        iterable $iterable,
        int $depth = 1,
    ): Generator
    {
        if ($depth <= 0) {
            throw new InvalidArgumentException("Expected: \$depth > 0. Got: {$depth}.", [
                'iterable' => $iterable,
                'depth' => $depth,
            ]);
        }

        return self::flattenImpl($iterable, $depth);
    }

    /**
     * Actual implementation for flatten.
     *
     * @template TKey of array-key
     * @param iterable<TKey, mixed> $iterable
     * Iterable to be traversed.
     * @param int $depth
     * Depth must be >= 1. Default: 1.
     * @return Generator<mixed, mixed>
     */
    protected static function flattenImpl(
        iterable $iterable,
        int $depth = 1,
    ): Generator
    {
        foreach ($iterable as $key => $val) {
            if (is_iterable($val) && $depth > 0) {
                foreach (self::flattenImpl($val, $depth - 1) as $_key => $_val) {
                    yield $_key => $_val;
                }
            } else {
                yield $key => $val;
            }
        }
    }

    /**
     * Returns an array which contains keys and values from `$iterable`
     * but with the `$search` value replaced with the `$replacement` value.
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
     * @return Generator<TKey, TValue>
     */
    public static function replace(
        iterable $iterable,
        mixed $search,
        mixed $replacement,
        ?int $limit = null,
        int &$count = 0,
    ): Generator
    {
        if ($limit < 0) {
            throw new InvalidArgumentException("Expected: \$limit >= 0. Got: {$limit}.", [
                'iterable' => $iterable,
                'search' => $search,
                'replacement' => $replacement,
                'limit' => $limit,
                'count' => $count,
            ]);
        }

        $count = 0;
        $limit ??= -1;
        $atLimit = false;
        foreach ($iterable as $key => $val) {
            if (!$atLimit && $val === $search) {
                yield $key => $replacement;
                ++$count;
                --$limit;
                if ($limit === 0) {
                    $atLimit = true;
                }
            } else {
                yield $key => $val;
            }
        }
    }

    /**
     * Creates a Generator that will send the key to the generator as value.
     *
     * @template TKey of array-key
     * @param iterable<TKey, mixed> $iterable
     * Iterable to be traversed.
     * @return Generator<int, TKey>
     */
    public static function keys(
        iterable $iterable,
    ): Generator
    {
        foreach ($iterable as $key => $item) {
            yield $key;
        }
    }

    /**
     * Creates a Generator that will send the result of the closure as value to the generator.
     *
     * @template TKey of array-key
     * @template TValue
     * @template TMapValue
     * @param iterable<TKey, TValue> $iterable
     * Iterable to be traversed.
     * @param Closure(TValue, TKey): TMapValue $callback
     * Closure which the result will be mapped as value.
     * @return Generator<TKey, TMapValue>
     */
    public static function map(
        iterable $iterable,
        Closure $callback,
    ): Generator
    {
        foreach ($iterable as $key => $val) {
            yield $key => $callback($val, $key);
        }
    }

    /**
     * Creates a Generator that will map and also flatten the result of the callback.
     *
     * @template TKey of array-key
     * @template TValue
     * @template TMapKey of array-key
     * @template TMapValue
     * @param iterable<TKey, TValue> $iterable
     * Iterable to be traversed.
     * @param Closure(TValue, TKey): iterable<TMapKey, TMapValue> $callback
     * Closure that will be called for each key/value. The returned value will be yielded.
     * @return Generator<TMapKey, TMapValue>
     */
    public static function mapWithKey(
        iterable $iterable,
        Closure $callback,
    ): Generator
    {
        foreach ($iterable as $key => $val) {
            foreach (self::verifyIterable($callback, $key, $val) as $k => $v) {
                yield $k => $v;
            }
        }
    }

    /**
     * Creates a Generator that will repeat through the iterable for a given amount of times.
     *
     * @template TKey of array-key
     * @template TValue
     * @param iterable<TKey, TValue> $iterable
     * Iterable to be traversed.
     * @param int $times
     * Amount of times the iterable will be repeated.
     * @return Generator<TKey, TValue>
     */
    public static function repeat(
        iterable $iterable,
        int $times,
    ): Generator
    {
        if ($times < 0) {
            throw new InvalidArgumentException("Expected: \$times >= 0. Got: {$times}.", [
                'iterable' => $iterable,
                'times' => $times,
            ]);
        }

        for ($i = 0; $i < $times; $i++) {
            foreach ($iterable as $key => $val) {
                yield $key => $val;
            }
        }
    }

    /**
     * Creates a Generator that will iterate starting from the offset up to the given length.
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
     * If set to **true** the array will be re-indexed.
     * @return Generator<TKey, TValue>
     */
    public static function slice(
        iterable $iterable,
        int $offset,
        int $length = PHP_INT_MAX,
        bool $reindex = false,
    ): Generator
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
                // @phpstan-ignore generator.keyType
                yield $val;
            } else {
                yield $key => $val;
            }
        }
    }

    /**
     * Creates a Generator which takes the given amount of values.
     *
     * @template TKey of array-key
     * @template TValue
     * @param iterable<TKey, TValue> $iterable
     * $iterable Iterable to be traversed.
     * @param int $amount
     * Amount of elements to take. Must be >= 0.
     * @return Generator<TKey, TValue>
     */
    public static function takeFirst(
        iterable $iterable,
        int $amount,
    ): Generator
    {
        if ($amount < 0) {
            throw new InvalidArgumentException("Expected: \$amount >= 0. Got: {$amount}.", [
                'iterable' => $iterable,
                'amount' => $amount,
            ]);
        }

        return self::slice($iterable, 0, $amount);
    }

    /**
     * Creates a Generator that yields sub-slices of `$size`.
     * Also known as sliding window.
     *
     * @template TKey of array-key
     * @template TValue
     * @param iterable<TKey, TValue> $iterable
     * Iterable to be traversed.
     * @param int $size
     * Size of the window. Must be >= 1.
     * @param bool $reindex
     * If set to **true** the array will be re-indexed.
     * @return Generator<int, array<TKey, TValue>>
     */
    public static function slide(
        iterable $iterable,
        int $size,
        bool $reindex = false,
    ): Generator
    {
        if ($size <= 0) {
            throw new InvalidArgumentException("Expected: \$size > 0. Got: {$size}.", [
                'iterable' => $iterable,
                'size' => $size,
                'reindex' => $reindex,
            ]);
        }

        $window = [];
        $filled = false;
        foreach ($iterable as $key => $val) {
            $reindex
                ? $window[] = $val
                : $window[$key] = $val;

            // Backfill until window size is at $size
            if ($filled === false) {
                $filled = count($window) === $size;
                if ($filled === false) {
                    continue;
                }
            }

            yield $window;

            $window = array_slice($window, 1, null, !$reindex);
        }

        if (!$filled) {
            yield $window;
        }
    }

    /**
     * Creates a Generator that will send the key/value to the generator if the condition is **true**.
     *
     * @template TKey of array-key
     * @template TValue
     * @param iterable<TKey, TValue> $iterable
     * Iterable to be traversed.
     * @param Closure(TValue, TKey): bool $condition
     * A condition that should return a boolean.
     * @param bool $reindex
     * [Optional] Result will be re-indexed if **true**.
     * @return Generator<TKey, TValue>
     */
    public static function takeIf(
        iterable $iterable,
        Closure $condition,
        bool $reindex = false,
    ): Generator
    {
        foreach ($iterable as $key => $val) {
            if (self::verifyBool($condition, $key, $val)) {
                if ($reindex) {
                    // @phpstan-ignore generator.keyType
                    yield $val;
                } else {
                    yield $key => $val;
                }
            }
        }
    }

    /**
     * Iterates over each element in $iterable and takes only the elements that are instances of the given class.
     *
     * @template TKey of array-key
     * @template TClass
     * @param iterable<TKey, mixed> $iterable
     * Iterable to be traversed.
     * @param class-string<TClass> $class
     * Class to be checked with `instanceof`.
     * @param bool $reindex
     * [Optional] Result will be re-indexed if **true**.
     * @return Generator<TKey, TClass>
     */
    public static function takeInstanceOf(
        iterable $iterable,
        string $class,
        bool $reindex = false,
    ): Generator
    {
        if (!class_exists($class)) {
            throw new InvalidArgumentException("Class: \"{$class}\" does not exist.", [
                'iterable' => $iterable,
                'class' => $class,
            ]);
        }

        foreach ($iterable as $key => $item) {
            if ($item instanceof $class) {
                if ($reindex) {
                    // @phpstan-ignore generator.keyType
                    yield $item;
                } else {
                    yield $key => $item;
                }
            }
        }
    }

    /**
     * Creates a Generator which iterates and sends key/value to the generator until the condition returns **true**.
     *
     * @template TKey of array-key
     * @template TValue
     * @param iterable<TKey, TValue> $iterable
     * Iterable to be traversed.
     * @param Closure(TValue, TKey): bool $condition
     * A break condition that should return false when loop should stop.
     * @return Generator<TKey, TValue>
     */
    public static function takeUntil(
        iterable $iterable,
        Closure $condition,
    ): Generator
    {
        foreach ($iterable as $key => $item) {
            if (!self::verifyBool($condition, $key, $item)) {
                yield $key => $item;
            } else {
                break;
            }
        }
    }

    /**
     * Creates a Generator which iterates and sends key/value to the generator while the condition returns **true**.
     *
     * @template TKey of array-key
     * @template TValue
     * @param iterable<TKey, TValue> $iterable
     * Iterable to be traversed.
     * @param Closure(TValue, TKey): bool $condition
     * A condition that should return a boolean value.
     * @return Generator<TKey, TValue>
     */
    public static function takeWhile(
        iterable $iterable,
        Closure $condition,
    ): Generator
    {
        foreach ($iterable as $key => $item) {
            if (self::verifyBool($condition, $key, $item)) {
                yield $key => $item;
            } else {
                break;
            }
        }
    }

    /**
     * Creates a Generator that will send only the value to the generator.
     *
     * @template TKey of array-key
     * @template TValue
     * @param iterable<TKey, TValue> $iterable
     * Iterable to be traversed.
     * @return Generator<int, TValue>
     */
    public static function values(
        iterable $iterable,
    ): Generator
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
    private static function verifyBool(
        Closure $condition,
        mixed $key,
        mixed $val,
    ): bool
    {
        return $condition($val, $key);
    }

    /**
     * Invoke the condition closure and make sure that it returns an iterable value.
     *
     * @template TKey of array-key
     * @template TValue
     * @template TMapKey of array-key
     * @template TMapValue
     * @param Closure(TValue, TKey): iterable<TMapKey, TMapValue> $condition
     * The condition that returns a boolean value.
     * @param TKey $key
     * the 2nd argument for the condition closure.
     * @param TValue $val
     * 1st argument for the condition closure.
     * @return iterable<TMapKey, TMapValue>
     */
    private static function verifyIterable(
        Closure $condition,
        mixed $key,
        mixed $val,
    ): iterable
    {
        return $condition($val, $key);
    }
}
