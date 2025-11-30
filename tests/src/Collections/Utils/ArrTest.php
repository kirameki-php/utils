<?php declare(strict_types=1);

namespace Tests\Kirameki\Collections\Utils;

use Closure;
use DateTime;
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
use Kirameki\Collections\Utils\Arr;
use Kirameki\Collections\Utils\Iter;
use Kirameki\Exceptions\InvalidArgumentException;
use Kirameki\Exceptions\InvalidTypeException;
use Kirameki\Exceptions\TypeMismatchException;
use Kirameki\Exceptions\UnreachableException;
use PHPUnit\Framework\Attributes\DoesNotPerformAssertions;
use Random\Engine\Xoshiro256StarStar;
use Random\Randomizer;
use stdClass;
use Tests\Kirameki\Collections\References\FixedNumEngine;
use Tests\Kirameki\Collections\TestCase;
use TypeError;
use function array_keys;
use function array_values;
use function in_array;
use function is_array;
use function is_int;
use function is_string;
use function ord;
use function range;
use function strlen;
use function substr;
use function tmpfile;
use function urlencode;
use const INF;
use const NAN;
use const SORT_NATURAL;

final class ArrTest extends TestCase
{
    public function test_append(): void
    {
        self::assertSame([], Arr::append([]), 'empty');
        self::assertSame([1, 2], Arr::append([1], 2), 'single');
        self::assertSame([1, 2, 3], Arr::append([1], 2, 3), 'multi');
        self::assertSame([1, 2], Arr::append([1], a: 2), 'named args');
        self::assertSame(
            [null, false, 0, '0', 'false'],
            Arr::append([], null, false, 0, '0', 'false'),
            'falsy'
        );
    }

    public function test_append_with_map(): void
    {
        $this->expectException(TypeMismatchException::class);
        $this->expectExceptionMessage('$array must be a list, map given.');
        $arr = ['a' => 1];
        Arr::append($arr, 1);
    }

    public function test_at(): void
    {
        self::assertSame(2, Arr::at([1, 2], 1));
    }

    public function test_at_on_empty(): void
    {
        $this->expectException(IndexOutOfBoundsException::class);
        $this->expectExceptionMessage('Size: 0 index: 0');
        self::assertSame(null, Arr::at([], 0));
    }

    public function test_at_missing_index(): void
    {
        $this->expectException(IndexOutOfBoundsException::class);
        $this->expectExceptionMessage('Size: 3 index: 3');
        self::assertSame(null, Arr::at([1, 2, 3], 3));
    }

    public function test_atOr(): void
    {
        // empty
        self::assertSame('x', Arr::atOr([], 0, 'x'));

        // not found and return null
        self::assertSame(null, Arr::atOr([1, 2, 3], 3, null));
        self::assertSame(null, Arr::atOr([1, 2, 3], -4, null));

        // return existing value
        self::assertSame(1, Arr::atOr([1, 2, 3], 3, 1));

        // miss with object
        $miss = new stdClass();
        self::assertSame($miss, Arr::atOr([1, 2, 3], 3, $miss));

        // hit
        self::assertSame(3, Arr::atOr([1, 2, 3], 2, null));

        // hit reverse
        self::assertSame(3, Arr::atOr([1, 2, 3], -1, null));
    }

    public function test_atOrNull(): void
    {
        self::assertSame(1, Arr::atOrNull([1, 2, 3], 0));
        self::assertSame(2, Arr::atOrNull([1, 2, 3], 1));
        self::assertSame(3, Arr::atOrNull([1, 2, 3], -1));

        self::assertSame(1, Arr::atOrNull(['a' => 1, 'b' => 2, 'c' => 3], 0));
        self::assertSame(2, Arr::atOrNull(['a' => 1, 'b' => 2, 'c' => 3], 1));
        self::assertSame(3, Arr::atOrNull(['a' => 1, 'b' => 2, 'c' => 3], -1));
    }

    public function test_average(): void
    {
        self::assertSame(1.0, Arr::average([1]), 'only one element');
        self::assertSame(1.5, Arr::average([1, 2]), 'using float');
        self::assertSame(2.0, Arr::average([1, 2, 3]), 'using int');
        self::assertSame(0.0, Arr::average([0, 0, 0]), 'all zeros');
        self::assertSame(2.0, Arr::average(['a' => 1, 'b' => 2, 'c' => 3]), 'from assoc');
    }

    public function test_average_not_empty(): void
    {
        $this->expectException(EmptyNotAllowedException::class);
        Arr::average([]);
    }

    public function test_average_with_NAN(): void
    {
        $this->expectException(InvalidElementException::class);
        $this->expectExceptionMessage('$iterable cannot contain NAN');
        Arr::average([NAN, -INF, INF]);
    }

    public function test_averageOrNull(): void
    {
        self::assertSame(null, Arr::averageOrNull([]), 'empty');
        self::assertSame(1.0, Arr::averageOrNull([1]), 'only one element');
        self::assertSame(1.5, Arr::averageOrNull([1, 2]), 'using float');
        self::assertSame(2.0, Arr::averageOrNull([1, 2, 3]), 'using int');
        self::assertSame(0.0, Arr::averageOrNull([0, 0, 0]), 'all zeros');
        self::assertSame(2.0, Arr::averageOrNull(['a' => 1, 'b' => 2, 'c' => 3]), 'from assoc');
    }

    public function test_averageOrNull_with_NAN(): void
    {
        $this->expectException(InvalidElementException::class);
        $this->expectExceptionMessage('$iterable cannot contain NAN');
        Arr::average([NAN, -INF, INF]);
    }

    public function test_chunk(): void
    {
        // empty
        self::assertEmpty(Arr::chunk([], 1));

        $chunked = Arr::chunk([1, 2, 3], 2);
        self::assertSame([[1, 2], [3]], $chunked);

        // size larger than items -> returns everything
        $chunked = Arr::chunk([1, 2, 3], 4);
        self::assertSame([[1, 2, 3]], $chunked);

        // size larger than items -> returns everything
        $chunked = Arr::chunk(['a' => 1, 'b' => 2, 'c' => 3], 4);
        self::assertSame([['a' => 1, 'b' => 2, 'c' => 3]], $chunked);

        // force reindex: false on list
        $chunked = Arr::chunk([1, 2, 3], 2, reindex: false);
        self::assertSame([[0 => 1, 1 => 2], [2 => 3]], $chunked);

        // force reindex: false on assoc
        $chunked = Arr::chunk(['a' => 1, 'b' => 2, 'c' => 3], 2);
        self::assertSame([['a' => 1, 'b' => 2], ['c' => 3]], $chunked);

        // force reindex: false on list
        $chunked = Arr::chunk([1, 2, 3], 2, reindex: true);
        self::assertSame([[1, 2], [3]], $chunked);

        // force reindex: false on assoc
        $chunked = Arr::chunk(['a' => 1, 'b' => 2, 'c' => 3], 2, reindex: true);
        self::assertSame([[1, 2], [3]], $chunked);

    }

    public function test_chunk_invalid_size(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Expected: $size >= 1. Got: 0.');
        Arr::chunk([1], 0);
    }

    public function test_clear(): void
    {
        // empty
        $list = [];
        Arr::clear($list);
        self::assertSame([], $list);

        // list
        $list = [1, 2, 3];
        Arr::clear($list);
        self::assertSame([], $list);

        // assoc
        $list = ['a' => 1, 'b' => 2, 'c' => 3];
        Arr::clear($list);
        self::assertSame([], $list);
    }

    public function test_coalesce(): void
    {
        self::assertSame(0, Arr::coalesce([null, 0, 1]), 'skip first');
        self::assertSame(0, Arr::coalesce([0, null, 1]), 'zero is valid');
        self::assertSame(1, Arr::coalesce([null, null, 1]), 'skip all nulls');
        self::assertSame('', Arr::coalesce(['', null, 1]), 'empty string is valid');
        self::assertSame([], Arr::coalesce([[], null, 1]), 'empty array is valid');
    }

    public function test_coalesce_empty(): void
    {
        $this->expectException(NoMatchFoundException::class);
        $this->expectExceptionMessage('Non-null value could not be found.');
        Arr::coalesce([]);
    }

    public function test_coalesce_only_null(): void
    {
        $this->expectException(NoMatchFoundException::class);
        $this->expectExceptionMessage('Non-null value could not be found.');
        Arr::coalesce([null]);
    }

    public function test_coalesceOrNull(): void
    {
        self::assertNull(Arr::coalesceOrNull([]), 'empty');
        self::assertSame(0, Arr::coalesceOrNull([null, 0, 1]), 'skip first');
        self::assertSame(0, Arr::coalesceOrNull([0, null, 1]), 'zero is valid');
        self::assertSame('', Arr::coalesceOrNull(['', null, 1]), 'empty string is valid');
        self::assertSame([], Arr::coalesceOrNull([[], null, 1]), 'empty array is valid');
        self::assertSame([], Arr::coalesceOrNull([null, [], 1]), 'empty array after null is valid');
        self::assertSame(1, Arr::coalesceOrNull([null, null, 1]), 'skip all nulls');
        self::assertSame(null, Arr::coalesceOrNull([null, null]), 'everything skipped and returns null');
    }

    public function test_contains(): void
    {
        self::assertFalse(Arr::contains([], null));

        // list: compared with value
        $list = [1, null, 2, [3], false];
        self::assertTrue(Arr::contains($list, 1));
        self::assertTrue(Arr::contains($list, null));
        self::assertTrue(Arr::contains($list, [3]));
        self::assertTrue(Arr::contains($list, false));
        self::assertFalse(Arr::contains($list, 3));
        self::assertFalse(Arr::contains($list, []));
        self::assertTrue(Arr::contains($this->toGenerator($list), 1));
        self::assertTrue(Arr::contains($this->toGenerator($list), null));
        self::assertTrue(Arr::contains($this->toGenerator($list), [3]));
        self::assertTrue(Arr::contains($this->toGenerator($list), false));
        self::assertFalse(Arr::contains($this->toGenerator($list), 3));
        self::assertFalse(Arr::contains($this->toGenerator($list), []));

        // assoc: compared with value
        $assoc = ['a' => 1];
        self::assertTrue(Arr::contains($assoc, 1));
        self::assertFalse(Arr::contains($assoc, ['a' => 1]));
        self::assertFalse(Arr::contains($assoc, ['a']));
        self::assertTrue(Arr::contains($this->toGenerator($assoc), 1));
        self::assertFalse(Arr::contains($this->toGenerator($assoc), ['a' => 1]));
        self::assertFalse(Arr::contains($this->toGenerator($assoc), ['a']));
    }

    public function test_containsAll(): void
    {
        self::assertTrue(Arr::containsAll([], []), 'empty args');
        self::assertFalse(Arr::containsAll([], [1]), 'empty iterable');
        self::assertTrue(Arr::containsAll([1], []), 'empty values');
        self::assertTrue(Arr::containsAll([1, 2, 3], [1]), 'match one');
        self::assertTrue(Arr::containsAll([1, 2], [1, 1, 1]), 'match same');
        self::assertTrue(Arr::containsAll([1, 2, 3], [2, 3]), 'match many');
        self::assertTrue(Arr::containsAll([1, 2, 3], [1, 2, 3]), 'match all');
        self::assertFalse(Arr::containsAll([1, 2, 3], [1, 4]), 'match one miss one');
        self::assertFalse(Arr::containsAll([1, 2, 3], [4, 5]), 'match zero');
    }

    public function test_containsAllKeys(): void
    {
        self::assertTrue(Arr::containsAllKeys([], []), 'empty args');
        self::assertFalse(Arr::containsAllKeys([], [1]), 'empty iterable');
        self::assertTrue(Arr::containsAllKeys([1], []), 'empty values');
        self::assertTrue(Arr::containsAllKeys(['a' => 1], ['a']), 'match one');
        self::assertTrue(Arr::containsAllKeys(['a' => 1, 'b' => 2, 'c' => 3], ['a', 'b']), 'match many');
        self::assertTrue(Arr::containsAllKeys(['a' => 1, 'b' => 2, 'c' => 3], ['a', 'b', 'c']), 'match all');
        self::assertFalse(Arr::containsAllKeys(['a' => 1, 'b' => 2], ['a', 'c']), 'match one miss one');
        self::assertFalse(Arr::containsAllKeys(['a' => 1, 'b' => 2], ['c', 'd']), 'match zero');
    }

    public function test_containsAny(): void
    {
        self::assertFalse(Arr::containsAny([], []), 'empty args');
        self::assertFalse(Arr::containsAny([], [1]), 'empty iterable');
        self::assertFalse(Arr::containsAny([1], []), 'empty values');
        self::assertTrue(Arr::containsAny([1, 2, 3], [1]), 'match one');
        self::assertTrue(Arr::containsAny([1, 2, 3], [2, 3]), 'match many');
        self::assertTrue(Arr::containsAny([1, 2, 3], [1, 2, 3]), 'match all');
        self::assertTrue(Arr::containsAny([1, 2, 3], [1, 4]), 'match one miss one');
        self::assertFalse(Arr::containsAny([1, 2, 3], [4, 5]), 'match zero');
    }

    public function test_containsAnyKeys(): void
    {
        self::assertFalse(Arr::containsAnyKeys([], []), 'empty args');
        self::assertFalse(Arr::containsAnyKeys([], [1]), 'empty iterable');
        self::assertFalse(Arr::containsAnyKeys([1], []), 'empty values');
        self::assertTrue(Arr::containsAnyKeys(['a' => 1, 'b' => 2], ['a']), 'match one');
        self::assertTrue(Arr::containsAnyKeys(['a' => 1, 'b' => 2, 'c' => 3], ['a', 'b']), 'match many');
        self::assertTrue(Arr::containsAnyKeys(['a' => 1, 'b' => 2, 'c' => 3], ['a', 'b', 'c']), 'match all');
        self::assertTrue(Arr::containsAnyKeys(['a' => 1, 'b' => 2], ['a', 'd']), 'match one miss one');
        self::assertFalse(Arr::containsAnyKeys(['a' => 1], ['d', 'e']), 'match zero');
    }

    public function test_containsKey(): void
    {
        // empty but not same instance
        $empty = [];
        self::assertFalse(Arr::containsKey($empty, 'a'));
        self::assertEmpty(Arr::containsKey($empty, 0));
        self::assertEmpty(Arr::containsKey($empty, -1));

        // copy sequence
        $list = [-2 => 1, 3, 4, [1, 2, [1, 2, 3]], [null]];
        self::assertTrue(Arr::containsKey($list, 1));
        self::assertTrue(Arr::containsKey($list, '1'));
        self::assertTrue(Arr::containsKey($list, '-2'));
        self::assertTrue(Arr::containsKey($list, -2));
        self::assertTrue(Arr::containsKey($list, -1));
        self::assertFalse(Arr::containsKey($list, 999));
        self::assertFalse(Arr::containsKey($list, '0.3'));
        self::assertTrue(Arr::containsKey($list, "2"));

        // copy assoc
        $assoc = ['a' => [1, 2, 3], '-' => 'c', 'd' => ['e'], 'f' => null];
        self::assertTrue(Arr::containsKey($assoc, 'a'));
        self::assertFalse(Arr::containsKey($assoc, 'a.a'));
        self::assertTrue(Arr::containsKey($assoc, 'f'));
    }

    public function test_containsNone(): void
    {
        self::assertTrue(Arr::containsNone([], []), 'empty both');
        self::assertTrue(Arr::containsNone([], [1, 2]), 'empty iterable');
        self::assertTrue(Arr::containsNone([1, 2], []), 'empty values');
        self::assertTrue(Arr::containsNone([1, 2], [3]), 'no match');
        self::assertFalse(Arr::containsNone([1, 2], [2]), 'partial match');
        self::assertFalse(Arr::containsNone([1, 2], [1, 1, 1]), 'same match');
        self::assertFalse(Arr::containsNone([1, 2], [1, 2]), 'full match');
        self::assertFalse(Arr::containsNone(['a' => 1, 'b' => 2], [1, 2]), 'map iterable');
        self::assertFalse(Arr::containsNone(['a' => 1, 'b' => 2], ['y' => 1, 'z' => 2]), 'map both');
    }

    public function test_containsSlice(): void
    {
        self::assertTrue(Arr::containsSlice([], []), 'empty both');
        self::assertFalse(Arr::containsSlice([], [1, 2]), 'empty iterable');
        self::assertTrue(Arr::containsSlice([1, 2], []), 'empty values');
        self::assertFalse(Arr::containsSlice([1, 2], [3]), 'simple slice');
        self::assertFalse(Arr::containsSlice([1, 2], [2, 3]), 'partial match fails');
        self::assertTrue(Arr::containsSlice([1, 2, 3, 4], [3, 4]), 'full match');
        self::assertFalse(Arr::containsSlice([1, 2, 3, 4], [2, 4]), 'not all match');
        self::assertFalse(Arr::containsSlice([1, 2], [2, 1]), 'exact match but opposite order');
        self::assertTrue(Arr::containsSlice([2, 2, 3], [2, 3]), 'full match');
        self::assertTrue(Arr::containsSlice(['a' => 1, 'b' => 2], [1, 2]), 'map iterable');
        self::assertTrue(Arr::containsSlice(['a' => 1, 'b' => 2], ['y' => 1, 'z' => 2]), 'map both');
    }

    public function test_count(): void
    {
        // empty
        self::assertSame(0, Arr::count([]));

        // count default
        self::assertSame(2, Arr::count([1, 2]));

        // count assoc
        self::assertSame(2, Arr::count(['a' => 1, 'b' => 2]));

        // empty with condition
        self::assertSame(0, Arr::count([], static fn() => true));

        // condition success
        self::assertSame(2, Arr::count([1, 2], static fn() => true));

        // condition fail
        self::assertSame(0, Arr::count([1, 2], static fn() => false));

        // condition partially success
        self::assertSame(2, Arr::count([1, 2, 3], static fn($v) => $v > 1));

        // condition checked with key
        self::assertSame(1, Arr::count(['a' => 1, 'b' => 2], static fn($v, $k) => $k === 'a'));
    }

    public function test_diff(): void
    {
        // empty array1
        self::assertSame([], Arr::diff([], [1]));

        // empty array2
        self::assertSame([1, 'a' => 1], Arr::diff([1, 'a' => 1], []));

        // array1 is list (re-indexed automatically)
        self::assertSame([2], Arr::diff([1, 2], [1, 3]));

        // array1 is assoc
        self::assertSame(['b' => 2], Arr::diff(['a' => 1, 'b' => 2], ['b' => 1, 'c' => 3]));

        // same values in list
        self::assertSame([], Arr::diff([1, 1], [1]));

        // same values in assoc
        self::assertSame([], Arr::diff([1, 'a' => 1, 'b' => 1], [1]));

        // array1 is list (re-indexed automatically)
        self::assertSame([2], Arr::diff([1, 2], [1, 3]));

        // array1 is assoc
        self::assertSame(['b' => 2], Arr::diff(['a' => 1, 'b' => 2], ['b' => 1, 'c' => 3]));

        // reindex: true on list
        self::assertSame([2], Arr::diff([1, 2], [1, 3], reindex: true));

        // reindex: true on assoc
        self::assertSame([2], Arr::diff(['a' => 1, 'b' => 2], ['b' => 1, 'c' => 3], reindex: true));

        // reindex: false on list
        self::assertSame([1 => 2], Arr::diff([1, 2], [1, 3], reindex: false));

        // reindex: false on assoc
        self::assertSame(['b' => 2], Arr::diff(['a' => 1, 'b' => 2], ['b' => 1, 'c' => 3], reindex: false));

        // with custom diff subject
        $diff = Arr::diff([[1], [2]], [[2], [3]], by: static fn(array $a, array $b) => $a[0] <=> $b[0]);
        self::assertSame([[1]], $diff);
    }

    public function test_diffKeys(): void
    {
        self::assertSame([], Arr::diffKeys([], [1]), 'empty array1');
        self::assertSame([1], Arr::diffKeys([1], []), 'empty array2');
        self::assertSame([2], Arr::diffKeys([1, 2], [3]), 'same values in list');
        self::assertSame(['a' => 1, 'b' => 2], Arr::diffKeys(['a' => 1, 'b' => 2], ['c' => 2]), 'unique keys but has same values');
        self::assertSame(['b' => 2], Arr::diffKeys(['a' => 1, 'b' => 2], ['a' => 2, 'c' => 3]), 'retain only on left side');
        self::assertSame([2], Arr::diffKeys([1, 2], [3], reindex: true), 'reindex: true on list');
        self::assertSame([2], Arr::diffKeys(['a' => 1, 'b' => 2], ['a' => 3], reindex: true), 'reindex: true on assoc');
        self::assertSame([1 => 2], Arr::diffKeys([1, 2], [3], reindex: false), 'reindex: false on list');
        self::assertSame(['b' => 2], Arr::diffKeys(['a' => 1, 'b' => 2], ['a' => 3], reindex: false), 'reindex: false on assoc');

        $by = static fn(string $a, string $b) => substr($a, 1) <=> substr($b, 1);
        $diff = Arr::diffKeys(['a1' => 0, 'b2' => 1], ['c1' => 2], $by);
        self::assertSame(['b2' => 1], $diff, 'with custom diff subject');
    }

    public function test_doesNotContain(): void
    {
        self::assertTrue(Arr::doesNotContain([], 0));
        self::assertTrue(Arr::doesNotContain([], null));
        self::assertTrue(Arr::doesNotContain([], []));
        self::assertTrue(Arr::doesNotContain([null, 0], false));
        self::assertTrue(Arr::doesNotContain([null, 0], 1));
        self::assertTrue(Arr::doesNotContain(['a' => 1], 'a'));
        self::assertFalse(Arr::doesNotContain([null, 0], null));
        self::assertFalse(Arr::doesNotContain([null, []], []));
        self::assertFalse(Arr::doesNotContain(['a' => 1, 0], 1));
    }

    public function test_doesNotContainKey(): void
    {
        self::assertTrue(Arr::doesNotContainKey([], 0));
        self::assertTrue(Arr::doesNotContainKey([], 1));
        self::assertTrue(Arr::doesNotContainKey(['b' => 1], 'a'));
        self::assertFalse(Arr::doesNotContainKey([1], 0));
        self::assertFalse(Arr::doesNotContainKey([11 => 1], 11));
        self::assertFalse(Arr::doesNotContainKey(['a' => 1, 0], 'a'));
    }

    public function test_dropEvery(): void
    {
        self::assertSame([], Arr::dropEvery([], 1), 'empty');
        self::assertSame([], Arr::dropEvery([1, 2, 3], 1), 'drop every 1st');
        self::assertSame([1, 3, 5], Arr::dropEvery([1, 2, 3, 4, 5], 2), 'drop every 2nd');
        self::assertSame([1, 2, 4, 5, 7], Arr::dropEvery(range(1, 7), 3), 'drop every 3rd');
        self::assertSame(['a' => 1], Arr::dropEvery(['a' => 1, 'b' => 2], 2), 'assoc');
    }

    public function test_dropEvery_zero_nth(): void
    {
        $this->expectExceptionMessage('Expected: $nth >= 1. Got: 0.');
        $this->expectException(InvalidArgumentException::class);
        Arr::dropEvery([], 0);
    }

    public function test_dropFirst(): void
    {
        // empty
        self::assertSame([], Arr::dropFirst([], 1));

        // drop nothing
        self::assertSame([1], Arr::dropFirst([1], 0));

        // drop list
        self::assertSame([1, 2], Arr::dropFirst([1, 1, 2], 1));

        // drop assoc
        self::assertSame(['b' => 2], Arr::dropFirst(['a' => 1, 'b' => 2], 1));

        // over value
        self::assertSame([], Arr::dropFirst(['a' => 1, 'b' => 2], 3));
    }

    public function test_dropFirst_fail_on_negative_amount(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Expected: $amount >= 0. Got: -1.');
        Arr::dropFirst(['a' => 1], -1);
    }

    public function test_dropIf(): void
    {
        self::assertSame([''], Arr::dropIf([null, ''], static fn($v) => $v === null), 'list: removes ones with condition');
        self::assertSame(['b' => null], Arr::dropIf(['a' => '', 'b' => null, 'c' => ''], static fn($v) => $v !== null), 'assoc: removes ones with condition');
        self::assertSame([''], Arr::dropIf([null, ''], static fn($v) => $v !== '', reindex: true), 'reindex: true');
        self::assertSame([1 => ''], Arr::dropIf([null, ''], static fn($v) => $v !== '', reindex: false), 'reindex: false');
    }

    public function test_dropKeys(): void
    {
        self::assertSame([], Arr::dropKeys([], []), 'empty array');
        self::assertSame(['a' => 1], Arr::dropKeys(['a' => 1], []), 'empty except');
        self::assertSame([2], Arr::dropKeys([1, 2, 3], [0, 2]), 'expect key (int)');
        self::assertSame(['b' => 2], Arr::dropKeys(['a' => 1, 'b' => 2], ['a']), 'expect key (string)');
        self::assertSame([2], Arr::dropKeys([1, 2, 3], [0, 2], reindex: true), 'reindex: true on list');
        self::assertSame([2], Arr::dropKeys(['a' => 1, 'b' => 2], ['a'], reindex: true), 'reindex: true on assoc');
        self::assertSame([1 => 2], Arr::dropKeys([1, 2, 3], [0, 2], reindex: false), 'reindex: false on list');
        self::assertSame(['b' => 2], Arr::dropKeys(['a' => 1, 'b' => 2], ['a'], reindex: false), 'reindex: false on assoc');
        self::assertSame(['b' => 2], Arr::dropKeys(['a' => 1, 'b' => 2], ['a', 'c'], false), 'safe: false');
    }

    public function test_dropKeys_safe_on_non_existing_keys(): void
    {
        $this->expectException(MissingKeyException::class);
        $this->expectExceptionMessage("Keys: [1, 2, 'b']");
        self::assertSame([], Arr::dropKeys([], [1, 2, 'b']));
    }

    public function test_dropLast(): void
    {
        // empty
        self::assertSame([], Arr::dropLast([], 1));

        // drop nothing
        self::assertSame([1], Arr::dropLast([1], 0));

        // drop list
        self::assertSame([1, 1], Arr::dropLast([1, 1, 2], 1));

        // drop assoc
        self::assertSame(['a' => 1], Arr::dropLast(['a' => 1, 'b' => 2], 1));

        // over value
        self::assertSame([], Arr::dropLast(['a' => 1, 'b' => 2], 3));
    }

    public function test_dropLast_fail_on_negative_amount(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Expected: $amount >= 0. Got: -1.');
        Arr::dropLast(['a' => 1], -1);
    }

    public function test_dropUntil(): void
    {
        // empty
        self::assertSame([], Arr::dropUntil([], static fn($v) => $v >= 3));

        // list
        self::assertSame([3, 4], Arr::dropUntil([1, 2, 3, 4], static fn($v) => $v >= 3));

        // look at value
        self::assertSame(
            ['c' => 3, 'd' => 4],
            Arr::dropUntil(['b' => 2, 'c' => 3, 'd' => 4], static fn($v, $k) => $v >= 3)
        );

        // look at key
        self::assertSame(
            ['c' => 3, 'd' => 4],
            Arr::dropUntil(['a' => 1, 'b' => 2, 'c' => 3, 'd' => 4], static fn($v, $k) => $k === 'c')
        );

        // reindex: true on list
        self::assertSame(
            [3, 4],
            Arr::dropUntil([1, 2, 3, 4], static fn($v) => $v >= 3, reindex: true)
        );

        // reindex: true on assoc
        self::assertSame(
            [4],
            Arr::dropUntil(['a' => 1, 'b' => 4], static fn($v) => $v >= 3, reindex: true)
        );

        // reindex: false on list
        self::assertSame(
            [2 => 3, 3 => 4],
            Arr::dropUntil([1, 2, 3, 4], static fn($v) => $v >= 3, reindex: false)
        );

        // reindex: false on assoc
        self::assertSame(
            ['b' => 4],
            Arr::dropUntil(['a' => 1, 'b' => 4], static fn($v) => $v >= 3, reindex: false)
        );

        // drop until null does not work
        $this->expectException(TypeError::class);
        $this->expectExceptionMessage(Iter::class . '::verifyBool(): Return value must be of type bool, null returned');
        Arr::dropUntil([1], static fn(int $v, int $k) => null);
    }

    public function test_dropWhile(): void
    {

        // empty
        self::assertSame([], Arr::dropWhile([], static fn($v) => $v <= 3));

        // list
        self::assertSame([3, 4], Arr::dropWhile([1, 2, 3, 4], static fn($v) => $v < 3));

        // look at value
        self::assertSame(
            ['c' => 3, 'd' => 4],
            Arr::dropWhile(['b' => 2, 'c' => 3, 'd' => 4], static fn($v, $k) => $v < 3)
        );

        // look at key
        self::assertSame(
            ['c' => 3, 'd' => 4],
            Arr::dropWhile(['a' => 1, 'b' => 2, 'c' => 3, 'd' => 4], static fn($v, $k) => $k !== 'c')
        );

        // reindex: true on list
        self::assertSame(
            [3, 4],
            Arr::dropWhile([1, 2, 3, 4], static fn($v) => $v < 3, reindex: true)
        );

        // reindex: true on assoc
        self::assertSame(
            [4],
            Arr::dropWhile(['a' => 1, 'b' => 4], static fn($v) => $v < 3, reindex: true)
        );

        // reindex: false on list
        self::assertSame(
            [2 => 3, 3 => 4],
            Arr::dropWhile([1, 2, 3, 4], static fn($v) => $v < 3, reindex: false)
        );

        // reindex: false on assoc
        self::assertSame(
            ['b' => 4],
            Arr::dropWhile(['a' => 1, 'b' => 4], static fn($v) => $v <= 3, reindex: false)
        );

        // drop while null does not work
        $this->expectException(TypeError::class);
        $this->expectExceptionMessage(Iter::class . '::verifyBool(): Return value must be of type bool, null returned');
        Arr::dropWhile([1], static fn(int $v, int $k) => null);
    }

    public function test_duplicates(): void
    {
        // empty
        self::assertSame([], Arr::duplicates([]));

        // null
        self::assertSame([null], Arr::duplicates([null, null]));

        // no dupes
        self::assertSame([], Arr::duplicates(['a' => 1, 'b' => 2]));

        // on list
        self::assertSame([4, 'a'], Arr::duplicates([5, 6, 4, 4, 'a', 'a', 'b']));

        // on assoc
        self::assertSame(['a' => 1], Arr::duplicates(['a' => 1, 'b' => 1, 'c' => 1, 'd' => 2]));

        // same object instance
        $instance = new stdClass();
        self::assertSame([$instance], Arr::duplicates([$instance, $instance]));

        // same resource instance
        $instance = tmpfile();
        self::assertSame([$instance], Arr::duplicates([$instance, $instance]));

        // different object instance
        self::assertSame([], Arr::duplicates([new stdClass(), new stdClass()]));
    }

    public function test_each(): void
    {
        // empty
        Arr::each([], static fn() => throw new UnreachableException());

        // list
        Arr::each(['a', 'b'], static function (string $v, int $k) {
            match ($k) {
                0 => self::assertSame('a', $v),
                1 => self::assertSame('b', $v),
                default => throw new UnreachableException(),
            };
        });

        // assoc
        Arr::each(['a' => 1, 'b' => 2], static function ($v, $k) {
            match ($k) {
                'a' => self::assertSame(['a' => 1], [$k => $v]),
                'b' => self::assertSame(['b' => 2], [$k => $v]),
                default => throw new UnreachableException(),
            };
        });
    }

    public function test_endsWith(): void
    {
        self::assertTrue(Arr::endsWith([], []), 'both empty');
        self::assertFalse(Arr::endsWith([], [1]), 'empty iterable');
        self::assertTrue(Arr::endsWith([1], []), 'empty values');
        self::assertTrue(Arr::endsWith([1, 2], [1, 2]), 'exact match');
        self::assertFalse(Arr::endsWith([1, 2, 3], [1, 2]), 'match start');
        self::assertTrue(Arr::endsWith([1, 2, 3], [2, 3]), 'match end');
        self::assertFalse(Arr::endsWith([1, 2, 3], [1, 2, 4]), 'partial match');
        self::assertTrue(Arr::endsWith(['a' => 1, 'b' => 2, 'c' => 3], ['b' => 2, 'c' => 3]), 'map: exact match');
        self::assertFalse(Arr::endsWith(['a' => 1, 'b' => 2, 'c' => 3], ['a' => 1, 'b' => 2]), 'map: match start');
        self::assertFalse(Arr::endsWith(['a' => 1, 'b' => 2, 'c' => 3], ['a' => 1, 'b' => 2, 'd' => 4]), 'map: partial match');
    }

    #[DoesNotPerformAssertions]
    public function test_ensureCountIs(): void
    {
        Arr::ensureCountIs([], 0); // empty
        Arr::ensureCountIs([1, 2], 2); // list
        Arr::ensureCountIs(['a' => 1, 'b' => 2], 2); // assoc;
    }

    public function test_ensureCountIs_mismatched_size(): void
    {
        $this->expectExceptionMessage('Expected count: 2, Got: 3.');
        $this->expectException(CountMismatchException::class);
        Arr::ensureCountIs([1, 2, 3], 2);
    }

    public function test_ensureElementType(): void
    {
        // on empty
        foreach (['int', 'float', 'bool', 'string', 'array', 'object'] as $type) {
            Arr::ensureElementType([], $type);
        }

        // valid primitive types
        Arr::ensureElementType([1], 'int');
        Arr::ensureElementType([1.0, INF, NAN], 'float');
        Arr::ensureElementType(['1', ''], 'string');
        Arr::ensureElementType([true, false], 'bool');
        Arr::ensureElementType([null, NULL], 'null');

        // valid complex types
        Arr::ensureElementType([[]], 'array');
        Arr::ensureElementType([new DateTime()], 'object');
        Arr::ensureElementType([date(...)], 'object');
        Arr::ensureElementType([date(...)], Closure::class);
        Arr::ensureElementType([1, 'string'], 'string|int');
        Arr::ensureElementType([1, null], 'int|null');

        $this->assertTrue(true, 'no exception');
    }

    public function test_ensureElementType_with_invalid_type(): void
    {
        $this->expectException(InvalidTypeException::class);
        $this->expectExceptionMessage('Invalid type: invalid');
        $this->vec([1])->ensureElementType('invalid');
    }

    public function test_ensureElementType_with_mismatch_value(): void
    {
        $this->expectExceptionMessage('Expected type: string|float, Got: int at 0.');
        $this->expectException(TypeMismatchException::class);
        $this->vec([1])->ensureElementType('string|float');
    }

    public function test_ensureExactKeys(): void
    {
        Arr::ensureExactKeys([], []); // empty
        Arr::ensureExactKeys(['a' => 1, 'b' => 2], ['a', 'b']); // exact keys
        $this->assertTrue(true);
    }

    public function test_ensureExactKeys_excess_keys(): void
    {
        $this->expectExceptionMessage("Keys: ['b'] should not exist.");
        $this->expectException(ExcessKeyException::class);
        Arr::ensureExactKeys(['a' => 1, 'b' => 2], ['a']);
    }

    public function test_ensureExactKeys_missing_keys(): void
    {
        $this->expectExceptionMessage("Keys: ['b'] did not exist.");
        $this->expectException(MissingKeyException::class);
        Arr::ensureExactKeys(['a' => 1], ['a', 'b']);
    }

    public function test_get(): void
    {
        $list = [1, 2];
        self::assertSame(2, Arr::get($list, 1));

        $assoc = ['a' => 1, 'b' => 2];
        self::assertSame(1, Arr::get($assoc, 'a'));
    }

    public function test_getOr(): void
    {
        $miss = new stdClass();
        $assoc = ['a' => 1, 'b' => 2, 'c' => null];
        self::assertSame(1, Arr::getOr($assoc, 'a', $miss));
        self::assertSame(null, Arr::getOr($assoc, 'c', $miss));
        self::assertSame($miss, Arr::getOr($assoc, 'd', $miss));
    }

    public function test_getOrNull(): void
    {
        $list = [1, 2];
        self::assertSame(2, Arr::getOrNull($list, 1));
        self::assertSame(null, Arr::getOrNull($list, 2));

        $assoc = ['a' => 1, 'b' => 2];
        self::assertSame(1, Arr::getOrNull($assoc, 'a'));
        self::assertSame(null, Arr::getOrNull($assoc, 'f'));
    }

    public function test_getOrFail_invalid_key_exception(): void
    {
        $this->expectException(InvalidKeyException::class);
        $this->expectExceptionMessage('2');
        Arr::get([1, 2], 2);
    }

    public function test_filter(): void
    {
        // list: removes ones with condition
        self::assertSame([''], Arr::filter([null, ''], static fn($v) => $v === ''));

        // assoc: removes ones with condition
        self::assertSame(['b' => ''], Arr::filter(['a' => null, 'b' => '', 'c' => null], static fn($v) => $v !== null));

        // reindex: true
        self::assertSame([''], Arr::filter([null, ''], static fn($v) => $v === '', reindex: true));

        // reindex: false
        self::assertSame([1 => ''], Arr::filter([null, ''], static fn($v) => $v === '', reindex: false));
    }

    public function test_first(): void
    {
        $list = [1, 2];
        self::assertSame(1, Arr::first($list));
        self::assertSame(2, Arr::first($list, static fn($v, $k) => $k === 1));
        self::assertSame(2, Arr::first($list, static fn($v, $k) => $v === 2));

        $assoc = ['a' => 1, 'b' => 2];
        self::assertSame(1, Arr::first($assoc));
        self::assertSame(2, Arr::first($assoc, static fn($v, $k) => $k === 'b'));
        self::assertSame(2, Arr::first($assoc, static fn($v, $k) => $v === 2));
    }

    public function test_first_empty(): void
    {
        $this->expectException(EmptyNotAllowedException::class);
        $this->expectExceptionMessage('$iterable must contain at least one element.');
        Arr::first([]);
    }

    public function test_first_bad_condition(): void
    {
        $this->expectException(NoMatchFoundException::class);
        $this->expectExceptionMessage('Failed to find matching condition.');
        Arr::first([1, 2], static fn(int $i) => $i > 2);
    }

    public function test_firstIndex(): void
    {
        self::assertSame(1, Arr::firstIndex([1, 2], 2), 'look for scalar on list');
        self::assertSame(1, Arr::firstIndex(['a' => 1, 'b' => 2], 2), 'look for scalar on map');
        self::assertSame(2, Arr::firstIndex([1, 2, "2"], "2"), 'look for scalar loosely');

        // list
        self::assertSame(2, Arr::firstIndex([10, 20, 20, 30], static fn($v, $k) => $k === 2));
        self::assertSame(1, Arr::firstIndex([10, 20, 20, 30], static fn($v, $k) => $v === 20));

        // assoc
        self::assertSame(1, Arr::firstIndex(['a' => 1, 'b' => 2, 'c' => 3], static fn($v, $k) => $v === 2));
        self::assertSame(2, Arr::firstIndex(['a' => 1, 'b' => 2, 'c' => 3], static fn($v, $k) => $k === 'c'));
        self::assertSame(1, Arr::firstIndex(['a' => 1, 'b' => 2, 'c' => 3], static fn($v, $k) => $v > 1));
    }

    public function test_firstIndex_on_empty_with_scalar_lookup(): void
    {
        $this->expectException(NoMatchFoundException::class);
        $this->expectExceptionMessage('Failed to find matching condition.');
        Arr::firstIndex([], 2);
    }

    public function test_firstIndex_on_empty_with_condition(): void
    {
        $this->expectException(NoMatchFoundException::class);
        $this->expectExceptionMessage('Failed to find matching condition.');
        Arr::firstIndex([], static fn($v, $k) => true);
    }

    public function test_firstIndexOrNull(): void
    {
        self::assertSame(null, Arr::firstIndexOrNull([], 2), 'look for scalar on empty');
        self::assertSame(1, Arr::firstIndexOrNull([1, 2], 2), 'look for scalar on list');
        self::assertSame(1, Arr::firstIndexOrNull(['a' => 1, 'b' => 2], 2), 'look for scalar on map');
        self::assertSame(null, Arr::firstIndexOrNull([1, 2], "2"), 'look for scalar loosely');

        self::assertSame(null, Arr::firstIndexOrNull([], static fn($v, $k) => true), 'closure empty');

        // list
        self::assertSame(2, Arr::firstIndexOrNull([10, 20, 20, 30], static fn($v, $k) => $k === 2));
        self::assertSame(1, Arr::firstIndexOrNull([10, 20, 20, 30], static fn($v, $k) => $v === 20));
        self::assertSame(null, Arr::firstIndexOrNull([10, 20, 20, 30], static fn() => false));

        // assoc
        self::assertSame(1, Arr::firstIndexOrNull(['a' => 1, 'b' => 2, 'c' => 3], static fn($v, $k) => $v === 2));
        self::assertSame(2, Arr::firstIndexOrNull(['a' => 1, 'b' => 2, 'c' => 3], static fn($v, $k) => $k === 'c'));
        self::assertSame(1, Arr::firstIndexOrNull(['a' => 1, 'b' => 2, 'c' => 3], static fn($v, $k) => $v > 1));
        self::assertSame(null, Arr::firstIndexOrNull(['a' => 1, 'b' => 2, 'c' => 3], static fn($v, $k) => $v > 10));
    }

    public function test_firstKey(): void
    {
        // list
        self::assertSame(1, Arr::firstKey([10, 20, 30], static fn($v, $k) => $v === 20));
        self::assertSame(2, Arr::firstKey([10, 20, 30], static fn($v, $k) => $k === 2));

        // assoc
        self::assertSame('b', Arr::firstKey(['a' => 1, 'b' => 2, 'c' => 3], static fn($v, $k) => $v === 2));
        self::assertSame('c', Arr::firstKey(['a' => 1, 'b' => 2, 'c' => 3], static fn($v, $k) => $k === 'c'));
    }

    public function test_firstKey_on_null(): void
    {
        $this->expectException(EmptyNotAllowedException::class);
        $this->expectExceptionMessage('$iterable must contain at least one element.');
        Arr::firstKey([]);
    }

    public function test_firstKey_on_no_match(): void
    {
        $this->expectException(NoMatchFoundException::class);
        $this->expectExceptionMessage('Failed to find matching condition.');
        Arr::firstKey([1, 2], static fn($v, $k) => false);
    }

    public function test_firstKeyOrNull(): void
    {
        // empty
        self::assertSame(null, Arr::firstKeyOrNull([], static fn($v, $k) => true));

        // list
        self::assertSame(null, Arr::firstKeyOrNull([10, 20, 20, 30], static fn() => false));
        self::assertSame(1, Arr::firstKeyOrNull([10, 20, 30], static fn($v, $k) => $v === 20));
        self::assertSame(2, Arr::firstKeyOrNull([10, 20, 30], static fn($v, $k) => $k === 2));

        // assoc
        self::assertSame(null, Arr::firstKeyOrNull(['a' => 1, 'b' => 2, 'c' => 3], static fn($v, $k) => $v > 10));
        self::assertSame('b', Arr::firstKeyOrNull(['a' => 1, 'b' => 2, 'c' => 3], static fn($v, $k) => $v === 2));
        self::assertSame('c', Arr::firstKeyOrNull(['a' => 1, 'b' => 2, 'c' => 3], static fn($v, $k) => $k === 'c'));
    }

    public function test_firstOr(): void
    {
        // empty
        self::assertSame(INF, Arr::firstOr([], INF));

        // list
        self::assertSame(1, Arr::firstOr([1, 2], INF));
        self::assertSame(2, Arr::firstOr([1, 2], INF, static fn($v, $k) => $k === 1));
        self::assertSame(2, Arr::firstOr([1, 2], INF, static fn($v, $k) => $v === 2));
        self::assertSame(INF, Arr::firstOr([1, 2], INF, static fn() => false));

        // assoc
        self::assertSame(1, Arr::firstOr(['a' => 1, 'b' => 2], INF));
        self::assertSame(2, Arr::firstOr(['a' => 1, 'b' => 2], INF, static fn($v, $k) => $k === 'b'));
        self::assertSame(2, Arr::firstOr(['a' => 1, 'b' => 2], INF, static fn($v, $k) => $v === 2));
        self::assertSame(INF, Arr::firstOr(['a' => 1, 'b' => 2], INF, static fn() => false));
    }

    public function test_firstOrNull(): void
    {
        self::assertSame(null, Arr::firstOrNull([]), 'empty');
        self::assertSame(null, Arr::firstOrNull([1, 2], static fn(int $i) => $i > 2), 'no match');
        self::assertSame(1, Arr::firstOrNull([1], static fn($v) => true), 'one element');

        $list = [10, 20];
        self::assertSame(10, Arr::firstOrNull($list), 'list');
        self::assertSame(20, Arr::firstOrNull($list, static fn($v, $k) => $k === 1), 'list');
        self::assertSame(20, Arr::firstOrNull($list, static fn($v, $k) => $v === 20), 'list');

        $assoc = ['a' => 10, 'b' => 20, 'c' => 30];
        self::assertSame(10, Arr::firstOrNull($assoc), 'assoc');
        self::assertSame(10, Arr::firstOrNull($assoc, static fn($v, $k) => $k === 'a'), 'assoc');
        self::assertSame(20, Arr::firstOrNull($assoc, static fn($v, $k) => $k === 'b'), 'assoc');
    }

    public function test_flatMap(): void
    {
        // empty
        self::assertSame([], Arr::flatMap([], static fn($i) => $i));

        // return modified array
        self::assertSame([1, -1, 2, -2], Arr::flatMap([1, 2], static fn($i) => [$i, -$i]));

        // simple flat
        self::assertSame(['a', 'b'], Arr::flatMap([['a'], ['b']], static fn($a) => $a));

        // keys are lost since it cannot be retained
        self::assertSame([1, 2], Arr::flatMap([['a' => 1], [2]], static fn($a) => $a));
    }

    public function test_flatMap_invalid_type(): void
    {
        $this->expectExceptionMessage(Iter::class . '::verifyIterable(): Return value must be of type Traversable|array, int returned');
        $this->expectException(TypeError::class);
        Arr::flatMap([[1], 2], static fn($a) => $a);
    }

    public function test_flatten(): void
    {
        // empty
        self::assertSame([], Arr::flatten([]));

        // nothing to flatten
        self::assertSame([1, 2], Arr::flatten([1, 2]));

        // flatten only 1 (default)
        self::assertSame([1, [2, 2], 3], Arr::flatten([[1, [2, 2]], 3]));

        // flatten depth at 2
        self::assertSame([1, 1, 2, [3, 3], 2, 1], Arr::flatten([[1], [1, [2, [3, 3], 2], 1]], 2));

        // assoc info is lost
        self::assertSame([1, 2, 3], Arr::flatten(['a' => 1, 'b' => ['b1' => 2, 'b2' => 3]]));

        // assoc info is lost variant
        self::assertSame(['a', 'b', 'd'], Arr::flatten([['a'], 'b', ['c' => 'd']]));
    }

    public function test_flatten_zero_depth(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Expected: $depth > 0. Got: 0.');
        self::assertSame([1, 2], Arr::flatten([1, 2], 0));
    }

    public function test_flatten_negative_depth(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Expected: $depth > 0. Got: -1.');
        self::assertSame([1, 2], Arr::flatten([1, 2], -1));
    }

    public function test_flip(): void
    {
        self::assertSame([1, 2], array_keys(Arr::flip([1, 2])));
        self::assertSame([0, 1], array_values(Arr::flip([1, 2])));

        self::assertSame(['b' => 'a', 'd' => 'c'], Arr::flip(['a' => 'b', 'c' => 'd']));
    }

    public function test_flip_invalid_key_type(): void
    {
        $this->expectException(InvalidKeyException::class);
        $this->expectExceptionMessage('Expected: array value of type int|string. Got: boolean.');
        Arr::flip([true, false]);
    }

    public function test_flip_duplicate_key(): void
    {
        $this->expectException(DuplicateKeyException::class);
        $this->expectExceptionMessage('Tried to overwrite existing key: 1');
        Arr::flip([1, 1]);
    }

    public function test_fold(): void
    {
        $reduced = Arr::fold([], 0, static fn(int $i) => $i + 1);
        self::assertSame(0, $reduced);

        $reduced = Arr::fold(['a' => 1, 'b' => 2], new stdClass(), static function (stdClass $c, int $i, string $k): stdClass {
            $c->$k = $i * 2;
            return $c;
        });
        self::assertSame(['a' => 2, 'b' => 4], (array)$reduced);

        $reduced = Arr::fold([1, 2, 3], 0, static fn(int $c, $i, $k): int => $c + $i);
        self::assertSame(6, $reduced);
    }

    public function test_from(): void
    {
        // empty
        self::assertSame([], Arr::from([]));

        // list
        self::assertSame([1, 2], Arr::from([1, 2]));

        // assoc
        self::assertSame(['a' => 1, 'b' => 2], Arr::from(['a' => 1, 'b' => 2]));

        // iterator list
        self::assertSame([1, 2], Arr::from((function () {
            yield 1;
            yield 2;
        })()));

        // iterator assoc
        self::assertSame(['a' => 1, 'b' => 2], Arr::from((function () {
            yield 'a' => 1;
            yield 'b' => 2;
        })()));
    }

    public function test_groupBy(): void
    {
        self::assertSame(
            [],
            Arr::groupBy([], fn(int $n) => $n % 3),
            'empty',
        );
        self::assertSame(
            [1 => [1, 4], 2 => [2, 5], 0 => [3, 6]],
            Arr::groupBy([1, 2, 3, 4, 5, 6], fn(int $n) => $n % 3),
            'basic usage',
        );
        self::assertSame(
            [1 => [1, 3], 0 => [2, 4]],
            Arr::groupBy(['a' => 1, 'b' => 2, 'c' => 3, 'd' => 4], fn(int $n) => $n % 2, reindex: true),
            'reindex: true'
        );
        self::assertSame(
            [1 => ['a' => 1, 'c' => 3], 0 => ['b' => 2, 'd' => 4]],
            Arr::groupBy(['a' => 1, 'b' => 2, 'c' => 3, 'd' => 4], fn(int $n) => $n % 2, reindex: false),
            'reindex: false',
        );
    }

    public function test_groupBy_missing_key(): void
    {
        $this->expectException(InvalidKeyException::class);
        $this->expectExceptionMessage('Expected: Grouping key of type int|string. Got: double.');
        Arr::groupBy([['dummy' => 3]], fn() => 1.1);
    }

    public function test_insertAt(): void
    {
        // empty
        $list = [];
        Arr::insertAt($list, 0, ['a']);
        self::assertSame(['a'], $list);

        // no values
        $list = [];
        Arr::insertAt($list, 0, []);
        self::assertSame([], $list);

        // list: at 0
        $list = [1, 2, 3, 4];
        Arr::insertAt($list, 0, ['a']);
        self::assertSame(['a', 1, 2, 3, 4], $list);

        // list: at 1
        $list = [1, 2, 3, 4];
        Arr::insertAt($list, 1, ['a']);
        self::assertSame([1, 'a', 2, 3, 4], $list);

        // list: at 1 multiple
        $list = [1, 2, 3, 4];
        Arr::insertAt($list, 1, ['a', 'b']);
        self::assertSame([1, 'a', 'b', 2, 3, 4], $list);

        // list: out of range
        $list = [1, 2, 3, 4];
        Arr::insertAt($list, 10, ['a']);
        self::assertSame([1, 2, 3, 4, 'a'], $list);

        // list: negative
        $list = [1, 2, 3, 4];
        Arr::insertAt($list, -1, ['a']);
        self::assertSame([1, 2, 3, 4, 'a'], $list);

        // list: negative alt
        $list = [1, 2, 3, 4];
        Arr::insertAt($list, -2, ['a']);
        self::assertSame([1, 2, 3, 'a', 4], $list);

        // assoc
        $assoc = [];
        Arr::insertAt($assoc, 0, ['a' => 2]);
        self::assertSame([2], $assoc);

        // assoc with index overflow
        $assoc = ['a' => 1];
        Arr::insertAt($assoc, 1, ['b' => 1, 'c' => 2]);
        self::assertSame(['a' => 1, 'b' => 1, 'c' => 2], $assoc);

        // assoc insert between
        $assoc = ['a' => 1, 'b' => 2];
        Arr::insertAt($assoc, 1, ['c' => 3]);
        self::assertSame(['a' => 1, 'c' => 3, 'b' => 2], $assoc);

        // insert array
        $list = [];
        Arr::insertAt($list, 0, [['a']]);
        self::assertSame([['a']], $list);
    }

    public function test_insertAt_fail_on_mixed_types(): void
    {
        $this->expectExceptionMessage('$values\' array type (list) does not match $array\'s (map).');
        $this->expectException(TypeMismatchException::class);
        $assoc = ['a' => 1];
        Arr::insertAt($assoc, 1, [1]);
    }

    public function test_insertAt_with_duplicate_key(): void
    {
        $this->expectExceptionMessage('Tried to overwrite existing key: a.');
        $this->expectException(DuplicateKeyException::class);
        $assoc = ['a' => 1];
        Arr::insertAt($assoc, 1, ['a' => 2]);
    }

    public function test_intersect(): void
    {
        self::assertSame([], Arr::intersect([], [1]), 'empty');
        self::assertSame([1], Arr::intersect([1, 2], [1]), 'right has more keys');
        self::assertSame([1], Arr::intersect([1], [1, 2]), 'left has more keys');
        self::assertSame([2, 3], Arr::intersect([1, 2, 3], [2, 3, 4]), 'mixed');
        self::assertSame(
            ['a' => 1],
            Arr::intersect(['a' => 1, 'b' => 2, 'c' => 3], ['b' => 1]),
            'with assoc',
        );
        self::assertSame(
            [1],
            Arr::intersect(['a' => 1, 'b' => 2, 'c' => 3], ['b' => 1], reindex: true),
            'reindex: true',
        );
        self::assertSame(
            ['a' => 1],
            Arr::intersect(['a' => 1, 'b' => 2, 'c' => 3], ['b' => 1], reindex: false),
            'reindex: false',
        );
    }

    public function test_intersect_mixed_types(): void
    {
        $this->expectException(TypeMismatchException::class);
        $this->expectExceptionMessage('$iterable1\'s inner type (map) does not match $iterable2\'s (list)');
        Arr::intersect(['a' => 1], [1]);
    }

    public function test_intersectKeys(): void
    {
        self::assertSame([], Arr::intersectKeys(['a' => 1], []), 'empty left');
        self::assertSame([], Arr::intersectKeys([], ['a' => 1]), 'empty right');
        self::assertSame([1, 2], Arr::intersectKeys([1, 2, 3], [1, 3]), 'on list');
        self::assertSame(
            ['a' => 1],
            Arr::intersectKeys(['a' => 1, 'b' => 2, 'c' => 3], ['a' => 2]),
            'assoc (left precedence)',
        );
    }

    public function test_intersectKeys_no_type_mixing(): void
    {
        $this->expectException(TypeMismatchException::class);
        $this->expectExceptionMessage('$iterable1\'s array type (map) does not match $iterable2\'s (list)');
        Arr::intersectKeys(['a' => 1], [1]);
    }

    public function test_isEmpty(): void
    {
        // empty
        self::assertTrue(Arr::isEmpty([]));

        // on list
        self::assertFalse(Arr::isEmpty([1, 2]));

        // on assoc
        self::assertFalse(Arr::isEmpty(['a' => 1, 'b' => 2]));
    }

    public function test_isList(): void
    {
        self::assertTrue(Arr::isList([]), 'empty');
        self::assertTrue(Arr::isList([1, 2]), 'list');
        self::assertFalse(Arr::isList(['a' => 1, 'b' => 2]), 'assoc');
        self::assertFalse(Arr::isList([1 => 1, 2 => 2]), 'unordered list');
    }

    public function test_isMap(): void
    {
        self::assertTrue(Arr::isMap([]), 'on empty');
        self::assertFalse(Arr::isMap([1, 2]), 'on list');
        self::assertTrue(Arr::isMap(['a' => 1, 'b' => 2]), 'on assoc');
        self::assertTrue(Arr::isMap([1 => 1, 2 => 2]), 'on unordered list');
    }

    public function test_isNotEmpty(): void
    {
        self::assertFalse(Arr::isNotEmpty([]), 'on empty');
        self::assertTrue(Arr::isNotEmpty([1, 2]), 'on list');
        self::assertTrue(Arr::isNotEmpty(['a' => 1, 'b' => 2]), 'on assoc');
    }

    public function test_join(): void
    {
        $empty = [];
        self::assertSame('', Arr::join($empty, ', '));
        self::assertSame('[', Arr::join($empty, ', ', '['));
        self::assertSame('[]', Arr::join($empty, ', ', '[', ']'));

        $list = [1, 2];
        self::assertSame('1, 2', Arr::join($list, ', '));
        self::assertSame('[1, 2', Arr::join($list, ', ', '['));
        self::assertSame('[1, 2]', Arr::join($list, ', ', '[', ']'));

        $assoc = ['a' => 1, 'b' => 2];
        self::assertSame('1, 2', Arr::join($assoc, ', '));
        self::assertSame('[1, 2', Arr::join($assoc, ', ', '['));
        self::assertSame('[1, 2]', Arr::join($assoc, ', ', '[', ']'));
    }

    public function test_keyAt(): void
    {
        $assoc = ['a' => 1, 'b' => 2];
        self::assertSame('a', Arr::keyAt($assoc, 0), 'first');
        self::assertSame('b', Arr::keyAt($assoc, 1), 'last');
        self::assertSame('b', Arr::keyAt($assoc, -1), 'last from negative');
        self::assertSame('a', Arr::keyAt($assoc, -2), 'last from negative');
    }

    public function test_keyAt_empty(): void
    {
        $this->expectExceptionMessage('$iterable did not contain the given index: 1.');
        $this->expectException(IndexOutOfBoundsException::class);
        Arr::keyAt([], 1);
    }

    public function test_keyAt_out_of_bounds_positive(): void
    {
        $this->expectExceptionMessage('$iterable did not contain the given index: 1.');
        $this->expectException(IndexOutOfBoundsException::class);
        Arr::keyAt(['a' => 1], 1);
    }

    public function test_keyAt_out_of_bounds_negative(): void
    {
        $this->expectExceptionMessage('$iterable did not contain the given index: -2.');
        $this->expectException(IndexOutOfBoundsException::class);
        Arr::keyAt(['a' => 1], -2);
    }

    public function test_keyAtOrNull(): void
    {
        $assoc = ['a' => 1, 'b' => 2];
        self::assertNull(Arr::keyAtOrNull([], 0), 'empty');
        self::assertSame('a', Arr::keyAtOrNull($assoc, 0), 'first');
        self::assertSame('b', Arr::keyAtOrNull($assoc, 1), 'last');
        self::assertSame('b', Arr::keyAtOrNull($assoc, -1), 'last from negative');
        self::assertSame('a', Arr::keyAtOrNull($assoc, -2), 'last from negative');
        self::assertNull(Arr::keyAtOrNull($assoc, 2), 'out of bounds positive');
        self::assertNull(Arr::keyAtOrNull($assoc, -3), 'out of bounds negative');
    }

    public function test_keyBy(): void
    {
        $assoc = Arr::keyBy([1, 2], static fn($v) => 'a' . $v);
        self::assertSame(['a1' => 1, 'a2' => 2], $assoc);

        $assoc = Arr::keyBy([['id' => 'b'], ['id' => 'c']], static fn($v): string => $v['id']);
        self::assertSame(['b' => ['id' => 'b'], 'c' => ['id' => 'c']], $assoc);
    }

    public function test_keyBy_with_duplicate_key(): void
    {
        $this->expectException(DuplicateKeyException::class);
        Arr::keyBy([['id' => 'b'], ['id' => 'b']], static fn($v): string => $v['id']);
    }

    public function test_keyBy_with_overwritten_key(): void
    {
        $array = Arr::keyBy([['id' => 'b', 1], ['id' => 'b', 2]], static fn($v): string => $v['id'], true);
        self::assertSame(['b' => ['id' => 'b', 2]], $array);

        $this->expectException(DuplicateKeyException::class);
        Arr::keyBy([['id' => 'b'], ['id' => 'b']], static fn(array $v): string => $v['id']);
    }

    public function test_keyBy_with_invalid_key(): void
    {
        $this->expectException(InvalidKeyException::class);
        Arr::keyBy([['id' => 'b', 1], ['id' => 'b', 2]], static fn($v) => false);
    }

    public function test_keys(): void
    {
        self::assertSame([], Arr::keys([]), 'empty');
        self::assertSame([0, 1], Arr::keys([1, 2]), 'on list');
        self::assertSame(['a', 'b'], Arr::keys(['a' => 1, 'b' => 2]), 'on assoc');
    }

    public function test_last(): void
    {
        self::assertSame(1, Arr::last([1]));
    }

    public function test_last_empty(): void
    {
        $this->expectException(EmptyNotAllowedException::class);
        $this->expectExceptionMessage('$iterable must contain at least one element.');
        Arr::last([]);
    }

    public function test_last_bad_condition(): void
    {
        $this->expectException(NoMatchFoundException::class);
        $this->expectExceptionMessage('Failed to find matching condition.');
        Arr::last([1, 2], static fn(int $i) => $i > 2);
    }

    public function test_lastIndex(): void
    {
        // no condition
        self::assertSame(2, Arr::lastIndex([10, 20, 20]));

        // with condition
        self::assertSame(1, Arr::lastIndex([10, 20, 20], static fn($v, $k) => $k === 1));
        self::assertSame(2, Arr::lastIndex([10, 20, 20], static fn($v, $k) => $v === 20));

        // with assoc
        self::assertSame(1, Arr::lastIndex(['a' => 10, 'b' => 20]));
        self::assertSame(1, Arr::lastIndex(['a' => 10, 'b' => 20, 'c' => 30], static fn($v, $k) => $k === 'b'));
    }

    public function test_lastIndex_on_empty(): void
    {
        $this->expectException(EmptyNotAllowedException::class);
        $this->expectExceptionMessage('$iterable must contain at least one element.');
        Arr::lastIndex([]);
    }

    public function test_lastIndex_on_no_match(): void
    {
        $this->expectException(NoMatchFoundException::class);
        $this->expectExceptionMessage('Failed to find matching condition.');
        Arr::lastIndex([1, 2], fn() => false);
    }

    public function test_lastIndexOrNull(): void
    {
        // empty
        self::assertSame(null, Arr::lastIndexOrNull([]));

        // empty with condition
        self::assertSame(null, Arr::lastIndexOrNull([], static fn($v, $k) => true));

        // no condition
        self::assertSame(2, Arr::lastIndexOrNull([10, 20, 20]));

        // with condition
        self::assertSame(1, Arr::lastIndexOrNull([10, 20, 20], static fn($v, $k) => $k === 1));
        self::assertSame(2, Arr::lastIndexOrNull([10, 20, 20], static fn($v, $k) => $v === 20));

        // no condition matched
        self::assertSame(null, Arr::lastIndexOrNull([10, 20, 20], static fn() => false));

        // with assoc
        self::assertSame(1, Arr::lastIndexOrNull(['a' => 10, 'b' => 20]));
        self::assertSame(1, Arr::lastIndexOrNull(['a' => 10, 'b' => 20, 'c' => 30], static fn($v, $k) => $k === 'b'));
    }

    public function test_lastKey(): void
    {
        $list = [10, 20, 20];
        self::assertSame(2, Arr::lastKey($list), 'list: get last key (index)');
        self::assertSame(2, Arr::lastKey($list, static fn($v, $k) => $v === 20), 'list: get last match on condition');

        $map = ['a' => 10, 'b' => 20, 'c' => 20];
        self::assertSame('c', Arr::lastKey($map), 'map: get last key');
        self::assertSame('b', Arr::lastKey($map, fn($v, $k) => in_array($k, ['a', 'b'], true)), 'map: match on key condition');
        self::assertSame('c', Arr::lastKey($map, fn($v, $k) => $v === 20), 'map: match on last condition matched');
    }

    public function test_lastKey_on_empty(): void
    {
        $this->expectException(EmptyNotAllowedException::class);
        $this->expectExceptionMessage('$iterable must contain at least one element.');
        Arr::lastKey([]);
    }

    public function test_lastKey_with_no_matches(): void
    {
        $this->expectException(NoMatchFoundException::class);
        $this->expectExceptionMessage('Failed to find matching condition.');
        Arr::lastKey([1, 2, 3], static fn() => false);
    }

    public function test_lastKeyOrNull(): void
    {
        // empty array returns null
        self::assertSame(null, Arr::lastKeyOrNull([]));

        $list = [10, 20, 20];

        // list: get last key (index)
        self::assertSame(2, Arr::lastKeyOrNull($list));

        // list: get last match on condition
        self::assertSame(2, Arr::lastKeyOrNull($list, static fn($v, $k) => $v === 20));

        // list: no match returns null
        self::assertSame(null, Arr::lastKeyOrNull($list, static fn($v, $k) => false));

        $assoc = ['a' => 10, 'b' => 20, 'c' => 20];

        // assoc: get last key
        self::assertSame('c', Arr::lastKeyOrNull($assoc));

        // assoc: match on key condition
        self::assertSame('b', Arr::lastKeyOrNull($assoc, static fn($v, $k) => in_array($k, ['a', 'b'], true)));

        // assoc: match on last condition matched
        self::assertSame('c', Arr::lastKeyOrNull($assoc, static fn($v, $k) => $v === 20));

        // assoc: no match returns null
        self::assertSame(null, Arr::lastKeyOrNull($assoc, static fn() => false));
    }

    public function test_lastOr(): void
    {
        $miss = new stdClass();

        // empty
        self::assertSame($miss, Arr::lastOr([], $miss));

        self::assertSame(20, Arr::lastOr([10, 20], $miss));
        self::assertSame(20, Arr::lastOr([10, 20], $miss, static fn($v, $k) => $k === 1));
        self::assertSame(20, Arr::lastOr([10, 20], $miss, static fn($v, $k) => $v === 20));
        self::assertSame($miss, Arr::lastOr([10, 20], $miss, static fn() => false));
    }

    public function test_lastOrNull(): void
    {
        // empty
        self::assertSame(null, Arr::lastOrNull([]));

        // no condition matched
        self::assertSame(null, Arr::lastOrNull([1, 2], static fn(int $i) => $i > 2));

        // with no condition
        self::assertSame(20, Arr::lastOrNull([10, 20]));

        // condition matched
        self::assertSame(20, Arr::lastOrNull([10, 20], static fn($v, $k) => $k === 1));
        self::assertSame(20, Arr::lastOrNull([10, 20], static fn($v, $k) => $v === 20));

        // with assoc
        self::assertSame(20, Arr::lastOrNull(['a' => 10, 'b' => 20]));
        self::assertSame(20, Arr::lastOrNull(['a' => 10, 'b' => 20, 'c' => 30], static fn($v, $k) => $k === 'b'));
    }

    public function test_map(): void
    {
        self::assertSame([], Arr::map([], static fn($i) => true), 'empty');
        self::assertSame([2, 4, 6], Arr::map([1, 2, 3], static fn($i) => $i * 2), '1st argument contains values');
        self::assertSame([0, 1, 2], Arr::map([1, 2, 3], static fn($i, $k) => $k), '2nd argument contains keys');
        self::assertSame(['a' => 2, 'b' => 4], Arr::map(['a' => 1, 'b' => 2], static fn($i) => $i * 2), 'assoc: retains key');
    }

    public function test_mapWithKey(): void
    {
        self::assertSame([], Arr::mapWithKey([], static fn($i) => $i), 'empty');
        self::assertSame(['a1' => 1, 'b2' => 2], Arr::mapWithKey(['a' => 1, 'b' => 2], fn($v, $k) => yield "$k$v" => $v), 'use generator');
        self::assertSame(['b' => 2], Arr::mapWithKey(['a' => 1], fn($v, $k) => ['b' => 2]), 'use array');
        self::assertSame(['b' => 2], Arr::mapWithKey(['a' => 1, 'b' => 2], fn($v, $k) => ['b' => 2], true), 'overwrite');
    }

    public function test_mapWithKey_with_invalid_callback(): void
    {
        $this->expectExceptionMessage(Iter::class . '::verifyIterable(): Return value must be of type Traversable|array, true returned');
        $this->expectException(TypeError::class);
        Arr::mapWithKey(['a' => 1, 'b' => 2], fn($v, $k) => true);
    }

    public function test_mapWithKey_with_duplicate_key(): void
    {
        $this->expectExceptionMessage('Tried to overwrite existing key: b.');
        $this->expectException(DuplicateKeyException::class);
        Arr::mapWithKey(['a' => 1, 'b' => 2], fn($v, $k) => ['b' => 2]);
    }

    public function test_max(): void
    {
        self::assertSame(10, Arr::max([1, 2, 3, 10, 1]), 'list');
        self::assertSame(100, Arr::max([100, 2, 3, 10, 1]));
        self::assertSame(90, Arr::max([1, 2, 3, 10, 1, 90, -100]));
        self::assertSame(1.1, Arr::max([1.1, 1.0, 0.9]), 'floats');
        self::assertSame(INF, Arr::max([INF, -INF]), 'obscure floats');
        self::assertSame(100, Arr::max(['a' => 1, 'b' => 100, 'c' => 10]), 'assoc');
        self::assertSame(2, Arr::max(['a' => 2, 'b' => 1], static fn($v, $k) => $v), 'max by value');
        self::assertSame(1, Arr::max(['a' => 2, 'b' => 1], static fn($v, $k) => ord($k)), 'max by key');
    }

    public function test_max_with_empty(): void
    {
        $this->expectException(EmptyNotAllowedException::class);
        $this->expectExceptionMessage('$iterable must contain at least one element.');
        Arr::max([]);
    }

    public function test_max_with_NAN(): void
    {
        $this->expectException(InvalidElementException::class);
        $this->expectExceptionMessage('$iterable cannot contain NAN');
        Arr::max([NAN, -INF, INF]);
    }

    public function test_maxOrNull(): void
    {
        self::assertSame(null, Arr::maxOrNull([]), 'empty');
        self::assertSame(10, Arr::maxOrNull([1, 2, 3, 10, 1]), 'list');
        self::assertSame(100, Arr::maxOrNull([100, 2, 3, 10, 1]));
        self::assertSame(90, Arr::maxOrNull([1, 2, 3, 10, 1, 90, -100]));
        self::assertSame(1.1, Arr::maxOrNull([1.1, 1.0, 0.9]), 'floats');
        self::assertSame(INF, Arr::maxOrNull([INF, -INF]), 'obscure floats');
        self::assertSame(100, Arr::maxOrNull(['a' => 1, 'b' => 100, 'c' => 10]), 'assoc');
        self::assertSame(2, Arr::maxOrNull(['a' => 2, 'b' => 1], fn($v, $k) => $v), 'max by value');
        self::assertSame(1, Arr::maxOrNull(['a' => 2, 'b' => 1], fn($v, $k) => ord($k)), 'max by key');
    }

    public function test_maxOrNull_with_NAN(): void
    {
        $this->expectException(InvalidElementException::class);
        $this->expectExceptionMessage('$iterable cannot contain NAN');
        Arr::maxOrNull([NAN, -INF, INF]);
    }

    public function test_merge(): void
    {
        self::assertSame([], Arr::merge([], []), 'empty');
        self::assertSame([1, [2]], Arr::merge([], [1, [2]]), 'empty left array');
        self::assertSame([1], Arr::merge([1], []), 'empty right array');
        self::assertSame([1, 2, 3, 4], Arr::merge([1, 2], [3, 4]), 'merge list');
        self::assertSame(['b' => 2], Arr::merge([], ['b' => 2]), 'empty left map');
        self::assertSame(['a' => 1], Arr::merge(['a' => 1], []), 'empty right map');
        self::assertSame(['0' => 1, 1, [2]], Arr::merge(['0' => 1], [1, [2]]), 'string(0) is considered a list');
        self::assertSame(['a' => [3]], Arr::merge(['a' => [1, 2]], ['a' => [3]]), 'latter array takes precedence');
        self::assertSame([1, 2, 3, 4], Arr::merge([1], [2, 3], [4]), 'merge 3 arrays');
    }

    public function test_merge_nothing(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('At least one iterable must be defined.');
        Arr::merge();
    }

    public function test_merge_with_list_and_map(): void
    {
        $this->expectException(TypeMismatchException::class);
        $this->expectExceptionMessage('Tried to merge list with map. Try converting the map to a list.');
        Arr::merge([1, 2], [3, 'a' => 4]);
    }

    public function test_mergeRecursive(): void
    {
        // empty
        self::assertSame([], Arr::mergeRecursive([], []));

        // basic merge list
        self::assertSame([1, 2, 3], Arr::mergeRecursive([1, 2], [3]));

        // basic merge assoc
        self::assertSame(['a' => 1, 'b' => 2], Arr::mergeRecursive(['a' => 1], ['b' => 2]));

        // latter takes precedence
        self::assertSame(['a' => 2], Arr::mergeRecursive(['a' => 1], ['a' => 2]));

        // don't mix value types like array_merge
        self::assertSame(['a' => ['c' => 1]], Arr::mergeRecursive(['a' => 1], ['a' => ['c' => 1]]));

        // complex merge
        $merged = Arr::mergeRecursive(['a' => ['b' => 1], 'd' => 4], ['a' => ['c' => 2], 'b' => 3]);
        self::assertSame(['a' => ['b' => 1, 'c' => 2], 'd' => 4, 'b' => 3], $merged);
    }

    public function test_mergeRecursive_with_list_and_map(): void
    {
        $this->expectException(TypeMismatchException::class);
        $this->expectExceptionMessage('Tried to merge list with map. Try converting the map to a list.');
        Arr::mergeRecursive(['a' => [1, 2]], ['a' => ['b' => 1]]);
    }

    public function test_min(): void
    {
        self::assertSame(0, Arr::min([1, 2, 3, 0, 1]), 'list');
        self::assertSame(-100, Arr::min([-100, 2, 3, 10, 1]));
        self::assertSame(-90, Arr::min([1, 2, 3, 10, 1, -90, 100]));
        self::assertSame(-100, Arr::min([1, 2, 3, 10, 1, 90, -100]));
        self::assertSame(0.9, Arr::min([1.1, 1.0, 0.9]), 'floats');
        self::assertSame(-INF, Arr::min([INF, -INF]), 'obscure floats');
        self::assertSame(1, Arr::min(['a' => 100, 'b' => 10, 'c' => 1]), 'assoc');
        self::assertSame(1, Arr::min(['a' => 2, 'b' => 1], static fn($v, $k) => $v), 'min by value');
        self::assertSame(2, Arr::min(['a' => 2, 'b' => 1], static fn($v, $k) => ord($k)), 'min by key');
    }

    public function test_min_with_empty(): void
    {
        $this->expectException(EmptyNotAllowedException::class);
        $this->expectExceptionMessage('$iterable must contain at least one element.');
        Arr::min([]);
    }

    public function test_min_with_NAN(): void
    {
        $this->expectException(InvalidElementException::class);
        $this->expectExceptionMessage('$iterable cannot contain NAN');
        Arr::min([0, 1, NAN, -INF, INF]);
    }

    public function test_minMax(): void
    {
        self::assertSame(
            ['min' => 1, 'max' => 1],
            Arr::minMax([1]),
            'only one array',
        );
        self::assertSame(
            ['min' => -100, 'max' => 10],
            Arr::minMax([1, 10, -100]),
            'basic usage',
        );
        self::assertSame(
            ['min' => 1, 'max' => 2],
            Arr::minMax([2, 1], static fn($v, $k) => $v),
            'with condition list',
        );
        self::assertSame(
            ['min' => 1, 'max' => 2],
            Arr::minMax(['a' => 2, 'b' => 1], static fn($v, $k) => $v),
            'with condition assoc',
        );
    }

    public function test_minMax_with_NAN(): void
    {
        $this->expectException(InvalidElementException::class);
        $this->expectExceptionMessage('$iterable cannot contain NAN');
        Arr::minMax([NAN, -INF, INF]);
    }

    public function test_minMax_empty(): void
    {
        $this->expectException(EmptyNotAllowedException::class);
        $this->expectExceptionMessage('$iterable must contain at least one element.');
        Arr::minMax([]);
    }

    public function test_minMaxOrNull(): void
    {
        self::assertSame(
            ['min' => 1, 'max' => 1],
            Arr::minMaxOrNull([1]),
            'only one array',
        );
        self::assertSame(
            ['min' => -100, 'max' => 10],
            Arr::minMaxOrNull([1, 10, -100]),
            'basic usage',
        );
        self::assertSame(
            ['min' => 1, 'max' => 2],
            Arr::minMaxOrNull([2, 1], static fn($v, $k) => $v),
            'with condition list',
        );
        self::assertSame(
            ['min' => 1, 'max' => 2],
            Arr::minMaxOrNull(['a' => 2, 'b' => 1], static fn($v, $k) => $v),
            'with condition assoc',
        );
        self::assertSame(
            null,
            Arr::minMaxOrNull([]),
            'empty array',
        );
        self::assertSame(
            null,
            Arr::minMaxOrNull([], static fn($v, $k) => $v),
            'empty with condition',
        );
    }

    public function test_of(): void
    {
        // empty
        self::assertSame([], Arr::of());

        // list
        self::assertSame([1, 2, 3], Arr::of(1, 2, 3));

        // assoc
        self::assertSame(['a' => 1, 'b' => 2], Arr::of(a: 1, b: 2));

        // assoc with int
        self::assertSame([1, 'a' => 2], Arr::of(1, a: 2));
    }

    public function test_padLeft(): void
    {
        // empty
        self::assertSame([], Arr::padLeft([], 0, 1));

        // not padded
        self::assertSame([1], Arr::padLeft([1], 0, 1));

        self::assertSame([0], Arr::padLeft([], 1, 0));
        self::assertSame([1, 1], Arr::padLeft([1], 2, 1));
        self::assertSame([2, 2, 1], Arr::padLeft([1], 3, 2));
    }

    public function test_padLeft_on_assoc(): void
    {
        $this->expectExceptionMessage('Padding can only be applied to a list, map given.');
        $this->expectException(TypeMismatchException::class);
        Arr::padLeft(['a' => 1], 1, 2);
    }

    public function test_padLeft_with_negative_length(): void
    {
        $this->expectExceptionMessage('Expected: $length >= 0. Got: -1');
        $this->expectException(InvalidArgumentException::class);
        Arr::padLeft([1], -1, 2);
    }

    public function test_padRight(): void
    {
        // empty
        self::assertSame([], Arr::padRight([], 0, 1));

        // not padded
        self::assertSame([1], Arr::padRight([1], 0, 1));

        // pad right
        self::assertSame([0], Arr::padRight([], 1, 0));
        self::assertSame([1, 1], Arr::padRight([1], 2, 1));
        self::assertSame([1, 2, 2], Arr::padRight([1], 3, 2));
    }

    public function test_padRight_on_assoc(): void
    {
        $this->expectExceptionMessage('Padding can only be applied to a list, map given.');
        $this->expectException(TypeMismatchException::class);
        Arr::padRight(['a' => 1], 1, 2);
    }

    public function test_padRight_with_negative_length(): void
    {
        $this->expectExceptionMessage('Expected: $length >= 0. Got: -1');
        $this->expectException(InvalidArgumentException::class);
        Arr::padRight([1], -1, 2);
    }

    public function test_partition(): void
    {
        self::assertSame([[], []], Arr::partition([], fn($v) => (bool)($v % 2)), 'empty');
        self::assertSame([[1, 2, 3], []], Arr::partition([1, 2, 3], fn($v) => is_int($v)), ' all true');
        self::assertSame([[], [1, 2, 3]], Arr::partition([1, 2, 3], fn($v) => $v === 0), ' all false');
        self::assertSame([[1, 3], [2]], Arr::partition([1, 2, 3], fn($v) => (bool)($v % 2)), 'list');
        self::assertSame([['a' => 1], ['b' => 2]], Arr::partition(['a' => 1, 'b' => 2], fn($v) => $v === 1), 'map');
        self::assertSame([[1], [2]], Arr::partition(['a' => 1, 'b' => 2], fn($v) => $v === 1, true), 'map');
    }

    public function test_pop(): void
    {
        // list
        $list = [1, 2];
        self::assertSame(2, Arr::pop($list));
        self::assertSame([1], $list);

        // assoc
        $assoc = ['a' => 1, 'b' => 2];
        self::assertSame(2, Arr::pop($assoc));
        self::assertSame(['a' => 1], $assoc);
    }

    public function test_pop_on_empty(): void
    {
        $this->expectException(EmptyNotAllowedException::class);
        $this->expectExceptionMessage('&$array must contain at least one element.');
        $list = [];
        Arr::pop($list);
    }

    public function test_popMany(): void
    {
        // empty
        $list = [];
        self::assertSame([], Arr::popMany($list, 1));
        self::assertSame([], $list);

        // list: 1
        $list = [1, 2];
        self::assertSame([2], Arr::popMany($list, 1));
        self::assertSame([1], $list);

        // list: 2
        $list = [1, 2];
        self::assertSame([1, 2], Arr::popMany($list, 2));
        self::assertSame([], $list);

        // assoc: 1
        $assoc = ['a' => 1, 'b' => 2];
        self::assertSame(['b' => 2], Arr::popMany($assoc, 1));
        self::assertSame(['a' => 1], $assoc);

        // assoc: 2
        $assoc = ['a' => 1, 'b' => 2];
        self::assertSame(['a' => 1, 'b' => 2], Arr::popMany($assoc, 2));
        self::assertSame([], $assoc);
    }

    public function test_popMany_zero_amount(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Expected: $amount >= 1. Got: 0.');
        $list = [1, 2];
        self::assertSame([], Arr::popMany($list, 0));
    }

    public function test_popMany_negative_amount(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Expected: $amount >= 1. Got: -1.');
        $list = [1, 2];
        self::assertSame([], Arr::popMany($list, -1));
    }

    public function test_popOrNull(): void
    {
        // empty
        $list = [];
        self::assertSame(null, Arr::popOrNull($list));

        // list
        $list = [1, 2];
        self::assertSame(2, Arr::popOrNull($list));
        self::assertSame([1], $list);

        // assoc
        $assoc = ['a' => 1, 'b' => 2];
        self::assertSame(2, Arr::popOrNull($assoc));
        self::assertSame(['a' => 1], $assoc);
    }

    public function test_prepend(): void
    {
        self::assertSame([], Arr::prepend([]), 'empty');
        self::assertSame([2, 1], Arr::prepend([1], 2), 'single');
        self::assertSame([2, 3, 1], Arr::prepend([1], 2, 3), 'multi');
        self::assertSame([2, 1], Arr::prepend([1], a: 2), 'named args');
        self::assertSame(
            [null, false, 0, '0', 'false'],
            Arr::prepend([], null, false, 0, '0', 'false'),
            'falsy'
        );
    }

    public function test_prepend_with_map(): void
    {
        $this->expectException(TypeMismatchException::class);
        $this->expectExceptionMessage('$array must be a list, map given.');
        $arr = ['a' => 1];
        Arr::prepend($arr, 1);
    }

    public function test_prioritize(): void
    {
        self::assertSame([], Arr::prioritize([], static fn() => true), 'empty');

        $prioritized = Arr::prioritize([1, 2, 3], static fn() => false);
        self::assertSame([1, 2, 3], $prioritized, 'no change');

        $prioritized = Arr::prioritize([1, 2, 3], static fn(int $i) => $i === 2);
        self::assertSame([2, 1, 3], $prioritized, 'list');

        $prioritized = Arr::prioritize([1, 2, 2, 2], static fn(int $i) => $i === 2, 2);
        self::assertSame([2, 2, 1, 2], $prioritized, 'limit');

        $prioritized = Arr::prioritize(['a' => 1, 'bc' => 2, 'de' => 2, 'b' => 2], static fn($_, string $k) => strlen($k) > 1);
        self::assertSame(['bc', 'de', 'a', 'b'], Arr::keys($prioritized), 'assoc');

        $prioritized = Arr::prioritize(['a' => 1, 'bc' => 2, 'de' => 2, 'b' => 2], static fn($_, string $k) => strlen($k) > 1, reindex: true);
        self::assertSame([0, 1, 2, 3], Arr::keys($prioritized), 'reindex: true');

        $prioritized = Arr::prioritize([1, 2, 3, 4], static fn($_, int $k) => $k > 1, reindex: true);
        self::assertSame([0, 1, 2, 3], Arr::keys($prioritized), 'reindex: false');

        $prioritized = Arr::prioritize(['a' => 0, 'b' => 1, 'c' => 2], fn($i) => $i > 0, 1, false);
        $this->assertSame(['b', 'a', 'c'], Arr::keys($prioritized), 'reindex: false, limit: 1');
    }

    public function test_product(): void
    {
        self::assertSame(1, Arr::product([]), 'empty');
        self::assertSame(8, Arr::product([2, 2, 2]), 'int');
        self::assertEqualsWithDelta(0.04, Arr::product([0.2, 0.2]), 0.00000000001, 'float');
        self::assertSame(6, Arr::product(['b' => 1, 'a' => 3, 'c' => 2]), 'assoc');
    }

    public function test_product_with_nan(): void
    {
        $this->expectExceptionMessage('$iterable cannot contain NAN.');
        $this->expectException(InvalidElementException::class);
        Arr::product([1, NAN]);
    }

    public function test_pull(): void
    {
        // list
        $list = [1, 2, 3];
        self::assertSame(2, Arr::pull($list, 1));
        self::assertSame([1, 3], $list);

        // assoc
        $assoc = ['a' => 1, 'b' => 2];
        self::assertSame(2, Arr::pull($assoc, 'b'));
        self::assertSame(['a' => 1], $assoc);
    }

    public function test_pull_on_empty(): void
    {
        $this->expectException(InvalidKeyException::class);
        $this->expectExceptionMessage('Tried to pull undefined key "1"');
        $empty = [];
        Arr::pull($empty, 1);
    }

    public function test_pull_undefined_key(): void
    {
        $this->expectException(InvalidKeyException::class);
        $this->expectExceptionMessage('Tried to pull undefined key "c"');
        $assoc = ['a' => 1, 'b' => 2];
        Arr::pull($assoc, 'c');
    }

    public function test_pullOr(): void
    {
        $miss = new stdClass();

        // empty
        $list = [];
        self::assertSame('_test_', Arr::pullOr($list, 1, '_test_'));

        // list
        $list = [1, 2, 3];
        self::assertSame(2, Arr::pullOr($list, 1, null));
        self::assertSame([1, 3], $list);

        // list miss
        $list = [1, 2, 3];
        self::assertSame($miss, Arr::pullOr($list, 3, $miss));
        self::assertSame([1, 2, 3], $list);

        // assoc
        $assoc = ['a' => 1, 'b' => 2];
        self::assertSame(2, Arr::pullOr($assoc, 'b', null));
        self::assertSame(['a' => 1], $assoc);

        // assoc miss
        $assoc = ['a' => null];
        self::assertSame($miss, Arr::pullOr($assoc, 'b', $miss));
        self::assertSame(['a' => null], $assoc);

        // reindex: false
        $list = [1, 2, 3];
        self::assertSame(2, Arr::pullOr($list, 1, null, false));
        self::assertSame([0 => 1, 2 => 3], $list);

        // reindex: true
        $assoc = ['a' => 1, 'b' => 2];
        self::assertSame(2, Arr::pullOr($assoc, 'b', null, true));
        self::assertSame([1], $assoc);
    }

    public function test_pullOrNull(): void
    {
        // empty
        $list = [];
        self::assertSame(null, Arr::pullOrNull($list, 1));

        // list
        $list = [1, 2, 3];
        self::assertSame(2, Arr::pullOrNull($list, 1));
        self::assertSame([1, 3], $list);

        // list: non-existent key
        $list = [1, 2, 3];
        self::assertSame(null, Arr::pullOrNull($list, 4));

        // assoc
        $assoc = ['a' => 1, 'b' => 2];
        self::assertSame(2, Arr::pullOrNull($assoc, 'b'));
        self::assertSame(['a' => 1], $assoc);

        // assoc: non-existent key
        $assoc = ['a' => 1, 'b' => 2];
        self::assertSame(null, Arr::pullOrNull($assoc, 'c'));

        // reindex: false
        $list = [1, 2, 3];
        self::assertSame(2, Arr::pullOrNull($list, 1, false));
        self::assertSame([0 => 1, 2 => 3], $list);

        // reindex: true
        $assoc = ['a' => 1, 'b' => 2];
        self::assertSame(1, Arr::pullOrNull($assoc, 'a', true));
        self::assertSame([2], $assoc);
    }

    public function test_pullMany(): void
    {
        // empty
        $list = [];
        $pulled = Arr::pullMany($list, [0, 1, 'a']);
        self::assertSame([], $list);
        self::assertSame([], $pulled);

        // list
        $list = [1, 2, 3];
        $pulled = Arr::pullMany($list, [0, 1]);
        self::assertSame([3], $list);
        self::assertSame([1, 2], $pulled);

        // assoc
        $assoc = ['a' => 1, 'b' => 2, 'c' => 3];
        $pulled = Arr::pullMany($assoc, ['a', 'c']);
        self::assertSame(['b' => 2], $assoc);
        self::assertSame(['a' => 1, 'c' => 3], $pulled);

        // assoc: miss some key
        $assoc = ['a' => 1];
        $pulled = Arr::pullMany($assoc, ['a', 'c']);
        self::assertSame([], $assoc);
        self::assertSame(['a' => 1], $pulled);
    }

    public function test_push(): void
    {
        // empty
        $list = [];
        Arr::push($list);
        self::assertSame([], $list, 'empty');

        // single
        $list = [1];
        Arr::push($list, 2);
        self::assertSame([1, 2], $list, 'single value');

        // multi
        $list = [1];
        Arr::push($list, 2, 3);
        self::assertSame([1, 2, 3], $list, 'multi values');

        // falsy
        $list = [];
        Arr::push($list, null, false, 0, '0', 'false');
        self::assertSame([null, false, 0, '0', 'false'], $list, 'falsy');
    }

    public function test_push_non_list(): void
    {
        $this->expectException(TypeMismatchException::class);
        $this->expectExceptionMessage('$array must be a list, map given.');
        $map = ['a' => 1, 'b' => 2];
        Arr::push($map, 1);
    }

    public function test_ratio(): void
    {
        self::assertSame(1.0, Arr::ratio([1], static fn() => true), 'one true');
        self::assertSame(0.0, Arr::ratio([1], static fn() => false), 'one false');
        self::assertSame(1.0, Arr::ratio([1, 2], static fn() => true), 'all true');
        self::assertSame(0.0, Arr::ratio([1, 2], static fn() => false), 'all false');
        self::assertSame(0.5, Arr::ratio([1, 2], static fn($i) => $i > 1), 'half');
        self::assertSame(1/3, Arr::ratio([1, 2, 3], static fn($i) => $i > 2), 'third');
        self::assertSame(1/2, Arr::ratio(['a' => 1, 'b' => 2], static fn($i) => $i > 1), 'assoc');
    }

    public function test_ratio_on_empty(): void
    {
        $this->expectExceptionMessage('$iterable must contain at least one element.');
        $this->expectException(EmptyNotAllowedException::class);
        Arr::ratio([], static fn() => true);
    }

    public function test_ratioOrNull(): void
    {
        self::assertNull(Arr::ratioOrNull([], static fn() => true), 'empty');
        self::assertSame(1.0, Arr::ratioOrNull([1], static fn() => true), 'one true');
        self::assertSame(0.0, Arr::ratioOrNull([1], static fn() => false), 'one false');
        self::assertSame(1.0, Arr::ratioOrNull([1, 2], static fn() => true), 'all true');
        self::assertSame(0.0, Arr::ratioOrNull([1, 2], static fn() => false), 'all false');
        self::assertSame(0.5, Arr::ratioOrNull([1, 2], static fn($i) => $i > 1), 'half');
        self::assertSame(1/3, Arr::ratioOrNull([1, 2, 3], static fn($i) => $i > 2), 'third');
        self::assertSame(1/2, Arr::ratioOrNull(['a' => 1, 'b' => 2], static fn($i) => $i > 1), 'assoc');
    }

    public function test_reduce(): void
    {
        // reduce with keys
        $reduced = Arr::reduce([0 => 0, 1 => 0, 2 => 0], static fn(int $c, $i, $k) => $c + $k);
        self::assertSame(3, $reduced);

        // reduce with values
        $reduced = Arr::reduce([1, 2, 3], static fn(int $c, $i, $k) => $c + $i);
        self::assertSame(6, $reduced);

        // assoc only one element
        $reduced = Arr::reduce(['a' => 1], static fn(int $c, $i, $k) => -100);
        self::assertSame(1, $reduced);

        // assoc more than one element
        $reduced = Arr::reduce(['a' => 1, 'b' => 2], static fn($val, $i) => $i * 2);
        self::assertSame(4, $reduced);
    }

    public function test_reduce_with_empty(): void
    {
        $this->expectException(EmptyNotAllowedException::class);
        $this->expectExceptionMessage('$iterable must contain at least one element.');
        Arr::reduce([], static fn($v) => $v);
    }

    public function test_reduceOr(): void
    {
        // reduce with values
        $reduced = Arr::reduceOr([1, 2, 3], static fn(int $c, $i, $k) => $c + $i, true);
        self::assertSame(6, $reduced);

        self::assertTrue(Arr::reduceOr([], static fn($v) => $v, true));
    }

    public function test_reduceOrNull(): void
    {
        // reduce with values
        $reduced = Arr::reduceOrNull([1, 2, 3], static fn(int $c, $i, $k) => $c + $i);
        self::assertSame(6, $reduced);

        self::assertNull(Arr::reduceOrNull([], static fn($v) => $v));
    }

    public function test_reduce_unable_to_guess_initial(): void
    {
        $this->expectException(EmptyNotAllowedException::class);
        $this->expectExceptionMessage('$iterable must contain at least one element.');
        Arr::reduce([], static fn($c, $i, $k) => $k);
    }

    public function test_reindex(): void
    {
        $array = [1, 2];
        Arr::reindex($array);
        self::assertSame([1, 2], $array);

        $array = [1 => 1, 2 => 2];
        Arr::reindex($array);
        self::assertSame([1, 2], $array);

        $array = [0 => 1, 'a' => 1];
        Arr::reindex($array);
        self::assertSame([1, 1], $array);

        $array = ['a' => 1, 'b' => 2];
        Arr::reindex($array);
        self::assertSame([1, 2], $array);
    }

    public function test_remove(): void
    {
        // empty
        $list = [];
        self::assertSame([], Arr::remove($list, 1));
        self::assertSame([], $list);

        // list
        $list = [1, 2, 1];
        self::assertSame([0, 2], Arr::remove($list, 1));
        self::assertSame([2], $list);

        // list with limit
        $list = [1, 2, 1];
        self::assertSame([0], Arr::remove($list, 1, 1));
        self::assertSame([2, 1], $list);

        // assoc
        $assoc = ['a' => 1, 'b' => 2, 'c' => 1];
        self::assertSame(['a', 'c'], Arr::remove($assoc, 1));
        self::assertSame(['b' => 2], $assoc);

        // assoc with limit
        $assoc = ['a' => 1, 'b' => 2, 'c' => 1];
        self::assertSame(['a'], Arr::remove($assoc, 1, 1));
        self::assertSame(['b' => 2, 'c' => 1], $assoc);

        // reindex: false
        $assoc = [1, 2, 1];
        self::assertSame([0, 2], Arr::remove($assoc, 1, reindex: false));
        self::assertSame([1 => 2], $assoc);

        // reindex: true
        $assoc = ['a' => 1, 'b' => 2, 'c' => 1];
        self::assertSame(['a', 'c'], Arr::remove($assoc, 1, reindex: true));
        self::assertSame([2], $assoc);
    }

    public function test_repeat(): void
    {
        // empty
        self::assertSame([], Arr::repeat([], 1));

        // Repeat single 3 times
        self::assertSame([1, 1, 1], Arr::repeat([1], 3));

        // Repeat multiple 3 times
        self::assertSame([1, 2, 1, 2], Arr::repeat([1, 2], 2));

        // Repeat hash 3 times (loses the keys)
        self::assertSame([1, 2, 1, 2], Arr::repeat(['a' => 1, 'b' => 2], 2));

        // Repeat 0 times (does nothing)
        self::assertSame([], Arr::repeat([1], 0));
    }

    public function test_repeat_negative_times(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Expected: $times >= 0. Got: -1.');
        self::assertSame([], Arr::repeat([1], -1));
    }

    public function test_replace(): void
    {
        self::assertSame([], Arr::replace([], 5, 0), 'empty');
        self::assertSame([null], Arr::replace([1], 1, null), 'to null');
        self::assertSame([1], Arr::replace([null], null, 1), 'from null');
        self::assertSame([1, 2], Arr::replace([1, 2], 5, 0), 'no match');
        self::assertSame([0, 2, 0], Arr::replace([1, 2, 1], 1, 0), '2 match');
        self::assertSame(['a' => 2], Arr::replace(['a' => 1], 1, 2), 'assoc');

        $count = 0;
        self::assertSame([1, 2], Arr::replace([1, 2], 5, 0, null, $count), 'with count empty');
        self::assertSame(0, $count);

        $count = 0;
        self::assertSame([0, 2, 0], Arr::replace([1, 2, 1], 1, 0, null, $count), 'with count 2 match');
        self::assertSame(2, $count);

        $count = 5;
        self::assertSame([1, 2], Arr::replace([1, 2], 5, 0, null, $count), 'check count reset');
        self::assertSame(0, $count);

        $count = 0;
        self::assertSame([0, 2, 1], Arr::replace([1, 2, 1], 1, 0, 1, $count), 'with count 2 match limit 1');
        self::assertSame(1, $count);
        $count = 0;
        self::assertSame([0, 2, 0], Arr::replace([1, 2, 1], 1, 0, 5, $count), 'with count 2 match limit 5');
        self::assertSame(2, $count);
    }

    public function test_replace_negative_limit(): void
    {
        $this->expectExceptionMessage('Expected: $limit >= 0. Got: -1.');
        $this->expectException(InvalidArgumentException::class);
        Arr::replace([1, 2, 1], 1, 0, -1);
    }

    public function test_reverse(): void
    {
        // empty
        self::assertSame([], Arr::reverse([]));

        // list
        self::assertSame([2, 1], Arr::reverse([1, 2]));

        // assoc with int
        self::assertSame([200 => 2, 100 => 1], Arr::reverse([100 => 1, 200 => 2]));

        // assoc
        self::assertSame(['b' => 2, 'a' => 1], Arr::reverse(['a' => 1, 'b' => 2]));
        self::assertSame(['b', 'a'], array_keys(Arr::reverse(['a' => 1, 'b' => 2])));

        // assoc with int
        self::assertSame([2, 'a' => 1], Arr::reverse(['a' => 1, 2]));

        // reindex: true
        self::assertSame([2, 1], Arr::reverse([1, 2], true));
        self::assertSame([0, 1], array_keys(Arr::reverse([1, 2], true)));

        // reindex: false
        self::assertSame([1 => 2, 0 => 1], Arr::reverse([1, 2], false));
        self::assertSame([1, 0], array_keys(Arr::reverse([1, 2], false)));
    }

    public function test_rotate(): void
    {
        // empty
        self::assertSame([], array_keys(Arr::rotate([], 1)));

        // none
        self::assertSame([], array_keys(Arr::rotate([1], 0)));

        // once
        self::assertSame(['b', 'c', 'a'], array_keys(Arr::rotate(['a' => 1, 'b' => 2, 'c' => 3], 1)));

        // twice
        self::assertSame(['c' => 3, 'a' => 1, 'b' => 2], Arr::rotate(['a' => 1, 'b' => 2, 'c' => 3], 2));

        // negative once
        self::assertSame(['c', 'a', 'b'], array_keys(Arr::rotate(['a' => 1, 'b' => 2, 'c' => 3], -1)));

        // negative twice
        self::assertSame(['b', 'c', 'a'], array_keys(Arr::rotate(['a' => 1, 'b' => 2, 'c' => 3], -2)));

        // list
        self::assertSame([2, 3, 1], Arr::rotate([1, 2, 3], 1));
        self::assertSame([3, 1, 2], Arr::rotate([1, 2, 3], 2));
        self::assertSame([1, 2, 3], Arr::rotate([1, 2, 3], 3));

        // reindex: true on list
        self::assertSame([2, 3, 1], Arr::rotate([1, 2, 3], 1, reindex: true));

        // reindex: true on assoc
        self::assertSame([3, 1, 2], Arr::rotate(['a' => 1, 'b' => 2, 'c' => 3], 2, reindex: true));

        // reindex: false on list
        self::assertSame([1, 2, 0], array_keys(Arr::rotate([1, 2, 3], 1, reindex: false)));

        // reindex: false on assoc
        self::assertSame(['c' => 3, 'a' => 1, 'b' => 2], Arr::rotate(['a' => 1, 'b' => 2, 'c' => 3], 2, reindex: false));
    }

    public function test_sample(): void
    {
        self::assertThat(
            Arr::sample(range(0, 10)),
            $this->logicalAnd(
                $this->greaterThanOrEqual(0),
                $this->lessThanOrEqual(10),
            ),
            'secure randomizer'
        );

        self::assertSame(
            6,
            Arr::sample(range(0, 10), new Randomizer(new Xoshiro256StarStar(5))),
            'with randomizer',
        );

        self::assertSame(
            0,
            Arr::sample(range(0, 10), FixedNumEngine::inRandomizer()),
            'custom randomizer',
        );
    }

    public function test_sample_Empty(): void
    {
        $this->expectException(EmptyNotAllowedException::class);
        $this->expectExceptionMessage('$iterable must contain at least one element.');
        Arr::sample([]);
    }

    public function test_sampleKey(): void
    {
        self::assertThat(
            Arr::sampleKey(range(0, 10)),
            $this->logicalAnd(
                $this->greaterThanOrEqual(0),
                $this->lessThanOrEqual(10),
            ),
            'default randomizer'
        );

        $randomizer = new Randomizer(new Xoshiro256StarStar(5));

        self::assertSame(
            6,
            Arr::sampleKey(range(0, 10), $randomizer),
            'with randomizer',
        );

        self::assertSame(
            2,
            Arr::sampleKey([10, 11, 12], $randomizer),
            'list',
        );

        self::assertSame(
            'a',
            Arr::sampleKey(['a' => 1, 'b' => 2], $randomizer),
            'map',
        );

        self::assertSame(
            0,
            Arr::sample(range(0, 10), FixedNumEngine::inRandomizer()),
            'custom randomizer',
        );
    }

    public function test_sampleKey_Empty(): void
    {
        $this->expectException(EmptyNotAllowedException::class);
        $this->expectExceptionMessage('$iterable must contain at least one element.');
        Arr::sampleKey([]);
    }

    public function test_sampleKeyOrNull(): void
    {
        self::assertThat(
            Arr::sampleKeyOrNull(range(0, 10)),
            $this->logicalAnd(
                $this->greaterThanOrEqual(0),
                $this->lessThanOrEqual(10),
            ),
            'default randomizer'
        );

        $randomizer = new Randomizer(new Xoshiro256StarStar(5));

        self::assertSame(
            6,
            Arr::sampleKeyOrNull(range(0, 10), $randomizer),
            'with randomizer',
        );

        self::assertSame(
            2,
            Arr::sampleKeyOrNull([10, 11, 12], $randomizer),
            'list',
        );

        self::assertSame(
            'a',
            Arr::sampleKeyOrNull(['a' => 1, 'b' => 2], $randomizer),
            'map',
        );

        self::assertSame(
            null,
            Arr::sampleKeyOrNull([], $randomizer),
            'empty',
        );
    }

    public function test_sampleKeys(): void
    {
        $list_10 = range(0, 10);
        $mapAlpha = ['a' => 0, 'b' => 1, 'c' => 2, 'd' => 3];
        $randomizer = new Randomizer(new Xoshiro256StarStar(100));

        self::assertThat(
            Arr::sampleKeys($list_10, 1)[0],
            $this->logicalAnd(
                $this->greaterThanOrEqual(0),
                $this->lessThanOrEqual(10),
            ),
            'default randomizer'
        );

        self::assertSame(
            [0, 4],
            Arr::sampleKeys($list_10, 2, false, $randomizer),
            'list without replacement',
        );

        self::assertSame(
            ['c', 'd'],
            Arr::sampleKeys($mapAlpha, 2, false, $randomizer),
            'map without replacement',
        );

        self::assertSame(
            [0, 9, 2, 3, 1, 5],
            Arr::sampleKeys($list_10, 6, true, $randomizer),
            'list with replacement',
        );

        self::assertSame(
            ['a', 'b', 'c', 'c'],
            Arr::sampleKeys($mapAlpha, 4, true, $randomizer),
            'map with replacement',
        );

        self::assertSame(
            [0],
            Arr::sampleKeys([1], 1, false, $randomizer),
            'sole list without replacement',
        );

        self::assertSame(
            [0],
            Arr::sampleKeys([1], 1, true, $randomizer),
            'sole list with replacement',
        );

        self::assertSame(
            [],
            Arr::sampleKeys([1], 0, false, $randomizer),
            'zero amount without replacement',
        );

        self::assertSame(
            [],
            Arr::sampleKeys([1], 0, true, $randomizer),
            'zero amount with replacement',
        );

        self::assertSame(
            [0],
            Arr::sampleKeys(range(0, 10), 1, false, FixedNumEngine::inRandomizer()),
            'custom randomizer without replacement',
        );

        self::assertSame(
            [0, 0],
            Arr::sampleKeys(['a'], 2, true),
            '$amount bigger than $iterable with replacement',
        );
    }

    public function test_sampleKeys_without_replacement_empty(): void
    {
        $this->expectException(EmptyNotAllowedException::class);
        $this->expectExceptionMessage('$iterable must contain at least one element.');
        Arr::sampleKeys([], 1);
    }

    public function test_sampleKeys_with_replacement_empty(): void
    {
        $this->expectException(EmptyNotAllowedException::class);
        $this->expectExceptionMessage('$iterable must contain at least one element.');
        Arr::sampleKeys([], 1, true);
    }

    public function test_sampleKeys_without_replacement_amount_bigger_than_array(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('$amount must be between 0 and size of $iterable');
        Arr::sampleKeys(['a'], 2);
    }

    public function test_sampleMany(): void
    {
        self::assertThat(
            Arr::sampleMany(range(0, 10), 1)[0],
            $this->logicalAnd(
                $this->greaterThanOrEqual(0),
                $this->lessThanOrEqual(10),
            ),
            'default randomizer'
        );

        $randomizer = new Randomizer(new Xoshiro256StarStar(100));

        self::assertSame(
            [0, 4],
            Arr::sampleMany(range(0, 10), 2, false, $randomizer),
            'list without replacement',
        );

        self::assertSame(
            [2, 3],
            Arr::sampleMany(['a' => 0, 'b' => 1, 'c' => 2, 'd' => 3], 2, false, $randomizer),
            'map without replacement',
        );

        self::assertSame(
            [1, 3, 1, 3, 7],
            Arr::sampleMany(range(0, 9), 5, true, $randomizer),
            'list with replacement',
        );

        self::assertSame(
            [1, 0, 1, 2],
            Arr::sampleMany(['a' => 0, 'b' => 1, 'c' => 2, 'd' => 3], 4, true, $randomizer),
            'map with replacement',
        );

        self::assertSame(
            [1],
            Arr::sampleMany([1], 1, false, $randomizer),
            'sole list without replacement',
        );

        self::assertSame(
            [1],
            Arr::sampleMany([1], 1, true, $randomizer),
            'sole list with replacement',
        );

        self::assertSame(
            [],
            Arr::sampleMany([1], 0, false, $randomizer),
            'zero amount without replacement',
        );

        self::assertSame(
            [],
            Arr::sampleMany([1], 0, true, $randomizer),
            'zero amount with replacement',
        );

        self::assertSame(
            [0],
            Arr::sampleMany(range(0, 10), 1, true, FixedNumEngine::inRandomizer()),
            'custom randomizer with replacement',
        );

        self::assertSame(
            [0],
            Arr::sampleMany(range(0, 10), 1, false, FixedNumEngine::inRandomizer()),
            'custom randomizer without replacement',
        );

        self::assertSame(
            ['a', 'a'],
            Arr::sampleMany(['a'], 2, true),
            '$amount bigger than size of $iterable with replacement',
        );
    }

    public function test_sampleMany_empty_without_replacement(): void
    {
        $this->expectException(EmptyNotAllowedException::class);
        $this->expectExceptionMessage('$iterable must contain at least one element.');
        Arr::sampleMany([], 1);
    }

    public function test_sampleMany_empty_with_replacement(): void
    {
        $this->expectException(EmptyNotAllowedException::class);
        $this->expectExceptionMessage('$iterable must contain at least one element.');
        Arr::sampleMany([], 1, true);
    }

    public function test_sampleMany_amount_bigger_than_array_without_replacement(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('$amount must be between 0 and size of $iterable');
        Arr::sampleMany(['a'], 2);
    }

    public function test_sampleOr(): void
    {
        self::assertThat(
            Arr::sampleOr(range(0, 10), 'fallback'),
            $this->logicalAnd(
                $this->greaterThanOrEqual(0),
                $this->lessThanOrEqual(10),
            ),
            'default randomizer'
        );

        self::assertSame(
            1,
            Arr::sampleOr([1], new Randomizer(new Xoshiro256StarStar())),
            'with randomizer',
        );

        self::assertSame(
            'fallback',
            Arr::sampleOr([], 'fallback'),
            'use fallback',
        );
    }

    public function test_sampleOrNull(): void
    {
        self::assertThat(
            Arr::sampleOrNull(range(0, 10)),
            $this->logicalAnd(
                $this->greaterThanOrEqual(0),
                $this->lessThanOrEqual(10),
            ),
            'default randomizer'
        );

        self::assertSame(
            1,
            Arr::sampleOrNull([1], new Randomizer(new Xoshiro256StarStar())),
            'with randomizer',
        );

        self::assertNull(
            Arr::sampleOrNull([]),
            'use fallback',
        );
    }

    public function test_satisfyAll(): void
    {
        // empty
        self::assertTrue(Arr::satisfyAll([], static fn($v) => is_int($v)));

        // list
        self::assertTrue(Arr::satisfyAll([1, 2, 3], static fn($v) => is_int($v)));

        // assoc
        self::assertTrue(Arr::satisfyAll(['a' => 1, 'b' => 2, 'c' => 3], static fn($v, $k) => is_string($k)));

        // assoc with int
        self::assertFalse(Arr::satisfyAll(['a' => 1, 'b' => 2, 'c' => 3, 4, '1'], static fn($k) => is_string($k)));
    }

    public function test_satisfyAny(): void
    {
        self::assertFalse(Arr::satisfyAny([], static fn() => true));

        self::assertTrue(Arr::satisfyAny([1, null, 2, [3], false], static fn($v) => true));
        self::assertFalse(Arr::satisfyAny([1, null, 2, [3], false], static fn($v) => false));
        self::assertTrue(Arr::satisfyAny([1, null, 2, [3], false], static fn($v) => is_array($v)));

        self::assertTrue(Arr::satisfyAny(['a' => 1, 'b' => 2], static fn($v, $k) => true));
        self::assertFalse(Arr::satisfyAny(['a' => 1, 'b' => 2], static fn($v) => false));
        self::assertTrue(Arr::satisfyAny(['a' => 1, 'b' => 2], static fn($v, $k) => $k === 'b'));
    }

    public function test_satisfyNone(): void
    {
        self::assertTrue(Arr::satisfyNone([], static fn($v) => is_int($v)), 'empty');
        self::assertFalse(Arr::satisfyNone([1, 1], static fn($v) => is_int($v)), 'list: all true');
        self::assertTrue(Arr::satisfyNone(['a', 'b'], static fn($v) => empty($v)), 'list all false');
        self::assertFalse(Arr::satisfyNone(['a' => 1], static fn($v, $k) => is_int($v)), 'assoc');
    }

    public function test_satisfyOnce(): void
    {
        // empty
        self::assertFalse(Arr::satisfyOnce([], static fn($v) => is_int($v)));

        // list one true
        self::assertTrue(Arr::satisfyOnce([1, null, 'a'], static fn($v) => is_int($v)));

        // list one false
        self::assertFalse(Arr::satisfyOnce([1, 1, 'a'], static fn($v) => is_int($v)));

        // list all true
        self::assertFalse(Arr::satisfyOnce([1, 1], static fn($v) => is_int($v)));

        // list all false
        self::assertFalse(Arr::satisfyOnce(['a', 'b'], static fn($v) => empty($v)));

        // assoc
        self::assertTrue(Arr::satisfyOnce(['a' => 1], static fn($v, $k) => is_int($v)));
    }

    public function test_set(): void
    {
        // set on empty
        $assoc = [];
        Arr::set($assoc, 'a', 1);
        self::assertSame(['a' => 1], $assoc);

        // overwrite
        $assoc = ['a' => 1];
        Arr::set($assoc, 'a', 2);
        self::assertSame(['a' => 2], $assoc);

        // assoc set null
        $assoc = ['a' => 1];
        Arr::set($assoc, 'a', null);
        self::assertSame(['a' => null], $assoc);

        // list: set index
        $assoc = [];
        Arr::set($assoc, 0, 1);
        self::assertSame([1], $assoc);

        // list: set index
        $assoc = [1, 2];
        Arr::set($assoc, 2, 3);
        self::assertSame([1, 2, 3], $assoc);

        // list: set null
        $assoc = [];
        Arr::set($assoc, 0, null);
        self::assertSame([null], $assoc);
    }

    public function test_setIfExists(): void
    {
        // Set when not exists
        $map = [];
        self::assertFalse(Arr::setIfExists($map, 'a', 1));
        self::assertSame([], $map);

        // Set on existing
        $map = ['a' => null];
        self::assertTrue(Arr::setIfExists($map, 'a', 1));
        self::assertSame(['a' => 1], $map);

        // Set null on existing
        $map = ['a' => 1];
        self::assertTrue(Arr::setIfExists($map, 'a', null));
        self::assertSame(['a' => null], $map);
    }

    public function test_setIfNotExists(): void
    {
        // Not Set on existing
        $map = ['a' => 1];
        self::assertFalse(Arr::setIfNotExists($map, 'a', 2));
        self::assertSame(['a' => 1], $map);

        // Set success
        $map = ['a' => 1];
        self::assertTrue(Arr::setIfNotExists($map, 'b', 2));
        self::assertSame(['a' => 1, 'b' => 2], $map);

        // Null is determined as set
        $map = ['a' => null];
        self::assertFalse(Arr::setIfNotExists($map, 'a', 2));
        self::assertSame(['a' => null], $map);
    }

    public function test_shift(): void
    {
        // list
        $list = [1];
        self::assertSame(1, Arr::shift($list));
        self::assertSame([], $list);

        // assoc
        $assoc = ['a' => 1];
        self::assertSame(1, Arr::shift($assoc));
        self::assertSame([], $assoc);

        // assoc variant
        $assoc = ['a' => ['b' => 1]];
        self::assertSame(['b' => 1], Arr::shift($assoc));
        self::assertSame([], $assoc);
    }

    public function test_shift_on_empty(): void
    {
        $this->expectException(EmptyNotAllowedException::class);
        $this->expectExceptionMessage('&$array must contain at least one element.');
        $list = [];
        Arr::shift($list);
    }

    public function test_shiftMany(): void
    {
        // empty
        $list = [];
        self::assertSame([], Arr::shiftMany($list, 1));
        self::assertSame([], $list);

        // one
        $list = [1, 2];
        self::assertSame([1], Arr::shiftMany($list, 1));
        self::assertSame([2], $list);

        // two
        $list = [1, 2];
        self::assertSame([1, 2], Arr::shiftMany($list, 2));
        self::assertSame([], $list);

        // amount over array size
        $list = [1, 2];
        self::assertSame([1, 2], Arr::shiftMany($list, 3));
        self::assertSame([], $list);

        // assoc
        $list = ['a' => 1, 'b' => 2];
        self::assertSame(['a' => 1], Arr::shiftMany($list, 1));
        self::assertSame(['b' => 2], $list);
    }

    public function test_shiftMany_zero_amount(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Expected: $amount >= 1. Got: 0.');
        $list = [1, 2];
        self::assertSame([], Arr::shiftMany($list, 0));
    }

    public function test_shiftMany_negative_amount(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Expected: $amount >= 1. Got: -1.');
        $list = [1, 2];
        self::assertSame([], Arr::shiftMany($list, -1));
    }

    public function test_shiftOrNull(): void
    {
        // empty
        $list = [];
        self::assertSame(null, Arr::shiftOrNull($list));

        // list
        $list = [1];
        self::assertSame(1, Arr::shiftOrNull($list));
        self::assertSame([], $list);

        // assoc
        $assoc = ['a' => 1];
        self::assertSame(1, Arr::shiftOrNull($assoc));
        self::assertSame([], $assoc);

        // assoc variant
        $assoc = ['a' => ['b' => 1]];
        self::assertSame(['b' => 1], Arr::shiftOrNull($assoc));
        self::assertSame([], $assoc);
    }

    public function test_shuffle(): void
    {
        self::assertEquals(
            ['a' => 1, 'b' => 2, 'c' => 3],
            Arr::shuffle(['a' => 1, 'b' => 2, 'c' => 3]),
            'secure randomizer',
        );

        $randomizer = new Randomizer(new Xoshiro256StarStar(100));

        self::assertSame(
            [2, 3, 4, 2, 1],
            Arr::shuffle([1, 2, 2, 3, 4], null, $randomizer),
            'list without replacement',
        );

        self::assertSame(
            ['a' => 1, 'd' => 4, 'b' => 2, 'c' => 3],
            Arr::shuffle(['a' => 1, 'b' => 2, 'c' => 3, 'd' => 4], null, $randomizer),
            'map without replacement',
        );

        // reindex: false
        self::assertSame([0, 2, 4, 1, 3], array_keys(Arr::shuffle([1, 2, 2, 3, 4], false, $randomizer)));
        self::assertSame(['a' => 1, 'd' => 4, 'c' => 3, 'b' => 2], Arr::shuffle(['a' => 1, 'b' => 2, 'c' => 3, 'd' => 4], false, $randomizer));

        // reindex: true
        self::assertSame([0, 1, 2, 3, 4], array_keys(Arr::shuffle([1, 2, 2, 3, 4], true, $randomizer)));
        self::assertSame([0, 1, 2, 3], array_keys(Arr::shuffle(['a' => 1, 'b' => 2, 'c' => 3, 'd' => 4], true, $randomizer)));
    }

    public function test_single(): void
    {
        // list
        self::assertSame(1, Arr::single([1]));

        // assoc
        self::assertSame(1, Arr::single(['a' => 1]));

        // with condition
        self::assertSame(2, Arr::single([1, 2, 3], static fn(int $i) => $i === 2));
    }

    public function test_single_zero_item(): void
    {
        $this->expectException(EmptyNotAllowedException::class);
        $this->expectExceptionMessage('$iterable must contain at least one element.');
        Arr::single([]);
    }

    public function test_single_no_match(): void
    {
        $this->expectException(NoMatchFoundException::class);
        $this->expectExceptionMessage('Failed to find matching condition.');
        Arr::single([1, 2], static fn() => false);
    }

    public function test_single_more_than_one_item(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Expected only one element in result. 2 given.');
        Arr::single([1, 2]);
    }

    public function test_slice(): void
    {
        // with offset
        self::assertSame([2, 3], Arr::slice([1, 2, 3], 1));

        // with negative offset
        self::assertSame([2, 3], Arr::slice([1, 2, 3], -2));

        // with overflow offset
        self::assertSame([], Arr::slice([1, 2, 3], 100));

        // with length
        self::assertSame([2, 3], Arr::slice([1, 2, 3], 1, 2));

        // with negative length
        self::assertSame([1, 2], Arr::slice([1, 2, 3], 0, -1));

        // with overflow length
        self::assertSame([2, 3], Arr::slice([1, 2, 3], 1, 100));

        // assoc
        self::assertSame(['b' => 2, 'c' => 3], Arr::slice(['a' => 1, 'b' => 2, 'c' => 3], 1, 2));

        // reindex: true
        self::assertSame([2], Arr::slice(['a' => 1, 'b' => 2, 'c' => 3], 1, 1, reindex: true));

        // reindex: false
        self::assertSame([1 => 2], Arr::slice([1, 2, 3], 1, 1, reindex: false));
    }

    public function test_slide(): void
    {
        self::assertSame([[1]], Arr::slide([1], 1, true), 'list 1 size 1 (exact)');
        self::assertSame([[1, 2]], Arr::slide([1, 2], 2, true), 'list 2 size 2 (exact)');
        self::assertSame([[1, 2], [2, 3]], Arr::slide([1, 2, 3], 2, true), 'list 3 size 2');
        self::assertSame([[1, 2], [1 => 2, 2 => 3]], Arr::slide([1, 2, 3], 2, false), 'list but no reindex');

        self::assertSame(
            [['a' => 1]],
            Arr::slide(['a' => 1], 1),
            'map 1 size 1',
        );
        self::assertSame(
            [['a' => 1], ['b' => 2]],
            Arr::slide(['a' => 1, 'b' => 2], 1),
            'map 2 size 1',
        );
        self::assertSame(
            [['a' => 1, 'b' => 2], ['b' => 2, 'c' => 3]],
            Arr::slide(['a' => 1, 'b' => 2, 'c' => 3], 2),
            'map 3 size 2',
        );
    }

    public function test_slide_negative_size(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Expected: $size > 0. Got: -1.');
        self::assertSame([], Arr::slide([], -1), 'empty');
    }

    public function test_slide_zero_size(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Expected: $size > 0. Got: 0.');
        self::assertSame([], Arr::slide([], 0), 'zero');
    }

    public function test_sort(): void
    {
        self::assertSame([1, 2, 3, 4], Arr::sort([4, 2, 1, 3], SortOrder::Ascending));
        self::assertSame([4, 3, 2, 1], Arr::sort([4, 2, 1, 3], SortOrder::Descending));
    }

    public function test_sortAsc(): void
    {
        // empty
        self::assertSame([], Arr::sortAsc([]));

        // list
        self::assertSame([1, 2, 3, 4], Arr::sortAsc([4, 2, 1, 3]));

        // assoc
        self::assertSame(['b' => 1, 'c' => 2, 'a' => 3], Arr::sortAsc(['a' => 3, 'b' => 1, 'c' => 2]));

        // with SORT_NATURAL flag
        self::assertSame(['2', '30', '100'], Arr::sortAsc(['30', '2', '100'], flag: SORT_NATURAL));

        // list: with condition
        self::assertSame([1, 2, 3, 4], Arr::sortAsc([4, 2, 1, 3], static fn($v) => $v));

        // assoc: with condition
        self::assertSame(['a' => 1, 'b' => 0, 'c' => 2], Arr::sortAsc(['b' => 0, 'a' => 1, 'c' => 2], static fn($v, $k) => $k));

        // reindex: true
        self::assertSame([1, 2, 3], Arr::sortAsc(['a' => 3, 'b' => 1, 'c' => 2], reindex: true));

        // reindex: false
        self::assertSame(['b' => 1, 'c' => 2, 'a' => 3], Arr::sortAsc(['a' => 3, 'b' => 1, 'c' => 2], reindex: false));
    }

    public function test_sortDesc(): void
    {
        // empty
        self::assertSame([], Arr::sortDesc([]));

        // list
        self::assertSame([4, 3, 2, 1], Arr::sortDesc([4, 2, 1, 3]));

        // assoc
        self::assertSame(['a' => 3, 'c' => 2, 'b' => 1], Arr::sortDesc(['a' => 3, 'b' => 1, 'c' => 2]));

        // with SORT_NATURAL flag
        self::assertSame(['100', '30', '2'], Arr::sortDesc(['30', '100', '2'], flag: SORT_NATURAL));

        // list: with condition
        self::assertSame([4, 3, 2, 1], Arr::sortDesc([4, 2, 1, 3], static fn($v) => $v));

        // assoc: with condition
        self::assertSame(['c' => 2, 'b' => 0, 'a' => 1], Arr::sortDesc(['b' => 0, 'a' => 1, 'c' => 2], static fn($v, $k) => $k));

        // reindex: true
        $sorted = Arr::sortDesc(['a' => 3, 'b' => 1, 'c' => 2], reindex: true);
        self::assertSame([0, 1, 2], array_keys($sorted));
        self::assertSame([3, 2, 1], array_values($sorted));

        // reindex: false
        $sorted = Arr::sortDesc(['a' => 3, 'b' => 1, 'c' => 2], reindex: false);
        self::assertSame(['a', 'c', 'b'], array_keys($sorted));
        self::assertSame([3, 2, 1], array_values($sorted));
    }

    public function test_sortByKeyAsc(): void
    {
        // empty
        self::assertSame([], Arr::sortByKeyAsc([]));

        // list stays the same
        self::assertSame([0, 1, 2], Arr::sortByKeyAsc([0, 1, 2]));

        // assoc
        $assoc = Arr::sortByKeyAsc(['b' => 0, 'a' => 1, 'c' => 2]);
        self::assertSame(['a' => 1, 'b' => 0, 'c' => 2], $assoc);

        // with SORT_NATURAL
        $assoc = Arr::sortByKeyAsc(['2' => 0, '100' => 1, '30' => 2], SORT_NATURAL);
        self::assertSame(['2' => 0, '30' => 2, '100' => 1], $assoc);
    }

    public function test_sortByKeysDesc(): void
    {
        // empty
        self::assertSame([], Arr::sortByKeyDesc([]));

        // list stays the same
        self::assertSame([2, 1, 0], array_keys(Arr::sortByKeyDesc([0, 1, 2])));

        // assoc
        $assoc = Arr::sortByKeyDesc(['b' => 0, 'a' => 1, 'c' => 2]);
        self::assertSame(['c' => 2, 'b' => 0, 'a' => 1], $assoc);

        // with SORT_NATURAL
        $assoc = Arr::sortByKeyDesc(['2' => 0, '100' => 1, '30' => 2], SORT_NATURAL);
        self::assertSame(['100' => 1, '30' => 2, '2' => 0], $assoc);
    }

    public function test_sortWith(): void
    {
        // empty
        self::assertSame([], Arr::sortWith([], static fn($a, $b) => 1));

        // list
        $list = Arr::sortWith([1, 3, 2], static fn($a, $b) => ($a < $b) ? -1 : 1);
        self::assertSame([1, 2, 3], $list);

        // assoc
        $assoc = Arr::sortWith(['b' => 1, 'a' => 3, 'c' => 2], static fn($a, $b) => ($a < $b) ? -1 : 1);
        self::assertSame(['b' => 1, 'c' => 2, 'a' => 3], $assoc);
    }

    public function test_sortWithKey(): void
    {
        $assoc = Arr::sortWithKey([1 => 'a', 3 => 'b', 2 => 'c'], static fn($a, $b) => ($a < $b) ? -1 : 1);
        self::assertSame([1 => 'a', 2 => 'c', 3 => 'b'], $assoc);
    }

    public function test_splitAfter(): void
    {
        $this->assertSame([], Arr::splitAfter([], fn() => true), 'empty');
        $this->assertSame([[1, 2, 3]], Arr::splitAfter([1, 2, 3], fn() => false), 'no match');
        $this->assertSame([[1], [2, 3]], Arr::splitAfter([1, 2, 3], fn($v) => $v === 1), 'split 1');
        $this->assertSame([[1], [2], [3], []], Arr::splitAfter([1, 2, 3], fn($v) => true), 'split every');
        $this->assertSame([[1, 2, 3], []], Arr::splitAfter([1, 2, 3], fn($v) => $v === 3), 'split at end');

        $this->assertSame(
            [['a' => 1, 'b' => 2], ['c' => 3]],
            Arr::splitAfter(['a' => 1, 'b' => 2, 'c' => 3], fn($v, $k) => $k === 'b'),
            'split map',
        );
    }

    public function test_splitAfterIndex(): void
    {
        $this->assertSame([[], []], Arr::splitAfterIndex([], 0), 'empty');
        $this->assertSame([[1], [2, 3]], Arr::splitAfterIndex([1, 2, 3], 0), 'split at 0');
        $this->assertSame([[1, 2], [3]], Arr::splitAfterIndex([1, 2, 3], 1), 'split at 2');
        $this->assertSame([[1, 2, 3], []], Arr::splitAfterIndex([1, 2, 3], 2), 'split at overflow');
        $this->assertSame([[1, 2, 3], []], Arr::splitAfterIndex([1, 2, 3], -1), 'split at -1');
        $this->assertSame([[1, 2], [3]], Arr::splitAfterIndex([1, 2, 3], -2), 'split at -2');
        $this->assertSame([[], [1, 2, 3]], Arr::splitAfterIndex([1, 2, 3], -4), 'split at negative overflow');
        $this->assertSame([['a' => 1], ['b' => 2]], Arr::splitAfterIndex(['a' => 1, 'b' => 2], 0), 'split on map');
        $this->assertSame([[1], [2]], Arr::splitAfterIndex(['a' => 1, 'b' => 2], 0, true), 'split on map');
    }

    public function test_splitBefore(): void
    {
        $this->assertSame([], Arr::splitBefore([], fn() => true), 'empty');
        $this->assertSame([[1, 2, 3]], Arr::splitBefore([1, 2, 3], fn() => false), 'no match');
        $this->assertSame([[], [1, 2, 3]], Arr::splitBefore([1, 2, 3], fn($v) => $v === 1), 'split 1');
        $this->assertSame([[], [1], [2], [3]], Arr::splitBefore([1, 2, 3], fn($v) => true), 'split every');
        $this->assertSame([[1, 2], [3]], Arr::splitBefore([1, 2, 3], fn($v) => $v === 3), 'split at end');

        $this->assertSame(
            [['a' => 1], ['b' => 2, 'c' => 3]],
            Arr::splitBefore(['a' => 1, 'b' => 2, 'c' => 3], fn($v, $k) => $k === 'b'),
            'split map',
        );
    }

    public function test_splitBeforeIndex(): void
    {
        $this->assertSame([[], []], Arr::splitBeforeIndex([], 0), 'empty');
        $this->assertSame([[], [1, 2, 3]], Arr::splitBeforeIndex([1, 2, 3], 0), 'split at 0');
        $this->assertSame([[1], [2, 3]], Arr::splitBeforeIndex([1, 2, 3], 1), 'split at 1');
        $this->assertSame([[1, 2], [3]], Arr::splitBeforeIndex([1, 2, 3], 2), 'split at 2');
        $this->assertSame([[1, 2, 3], []], Arr::splitBeforeIndex([1, 2, 3], 3), 'split at overflow');
        $this->assertSame([[1, 2], [3]], Arr::splitBeforeIndex([1, 2, 3], -1), 'split at -1');
        $this->assertSame([[1], [2, 3]], Arr::splitBeforeIndex([1, 2, 3], -2), 'split at -2');
        $this->assertSame([[], [1, 2, 3]], Arr::splitBeforeIndex([1, 2, 3], -3), 'split at negative overflow');
        $this->assertSame([['a' => 1], ['b' => 2]], Arr::splitBeforeIndex(['a' => 1, 'b' => 2], 1), 'split on map');
        $this->assertSame([[1], [2]], Arr::splitBeforeIndex(['a' => 1, 'b' => 2], 1, true), 'split on map');
    }

    public function test_splitEvenly(): void
    {
        $this->assertSame([], Arr::splitEvenly([], 1), 'empty');
        $this->assertSame([[1], [2]], Arr::splitEvenly([1, 2], 2), 'split 1');
        $this->assertSame([[1, 2], [3]], Arr::splitEvenly([1, 2, 3], 2), 'split 2 on size: 3');
        $this->assertSame([[1, 2], [3, 4]], Arr::splitEvenly([1, 2, 3, 4], 2), 'split 2 on size: 4');
        $this->assertSame([[1, 2]], Arr::splitEvenly([1, 2], 1), 'exact');
        $this->assertSame([[1], [2]], Arr::splitEvenly([1, 2], 4), 'overflow');
        $this->assertSame([['a' => 1, 'b' => 2], ['c' => 3]], Arr::splitEvenly(['a' => 1, 'b' => 2, 'c' => 3], 2), 'map: split 2 on size: 3');
    }

    public function test_splitEvenly_zero_parts(): void
    {
        $this->expectExceptionMessage('Expected: $parts > 0. Got: 0.');
        $this->expectException(InvalidArgumentException::class);
        Arr::splitEvenly([], 0);
    }

    public function test_startsWith(): void
    {
        $this->assertTrue(Arr::startsWith([], []), 'empty both');
        $this->assertTrue(Arr::startsWith([1], []), 'empty values');
        $this->assertFalse(Arr::startsWith([], [1]), 'empty list');
        $this->assertTrue(Arr::startsWith([1, 2], [1, 2]), 'exact match');
        $this->assertTrue(Arr::startsWith([1, 2], [1]), 'start match');
        $this->assertFalse(Arr::startsWith([1, 2], [2]), 'end match');
        $this->assertFalse(Arr::startsWith([1, 2], [1, 2, 3]), 'values bigger');

        $this->assertTrue(Arr::startsWith(['a' => 1], []), 'empty values');
        $this->assertTrue(Arr::startsWith(['a' => 1, 'b' => 2], ['a' => 1]), 'start match');
        $this->assertTrue(Arr::startsWith(['a' => 1, 'b' => 2], ['a' => 1, 'b' => 2]), 'exact match');
        $this->assertTrue(Arr::startsWith(['a' => 1, 'b' => 2], [1]), 'key does not matter');
        $this->assertTrue(Arr::startsWith(['a' => 1, 'b' => 2], ['c' => 1]), 'key does not matter 2');
        $this->assertFalse(Arr::startsWith(['a' => 1, 'b' => 2], ['c' => 2]), 'different value');
    }

    public function test_sum(): void
    {
        self::assertSame(0, Arr::sum([]), 'empty');
        self::assertSame(3, Arr::sum([1, 1, 1]), 'int');
        self::assertEqualsWithDelta(0.3, Arr::sum([0.1, 0.2]), 0.001, 'float');
        self::assertSame(6, Arr::sum(['b' => 1, 'a' => 3, 'c' => 2]), 'map');
    }

    public function test_sum_throw_on_sum_of_string(): void
    {
        $this->expectException(TypeError::class);
        $this->expectExceptionMessage('Unsupported operand types: int + string');
        Arr::sum(['a', 'b']);
    }

    public function test_sum_with_NAN(): void
    {
        $this->expectException(InvalidElementException::class);
        $this->expectExceptionMessage('$iterable cannot contain NAN');
        Arr::sum([NAN]);
    }

    public function test_swap(): void
    {
        $this->assertSame([1, 3, 2, 4], Arr::swap([1, 2, 3, 4], 1, 2), 'swap list');
        $this->assertSame(['c' => 3, 'b' => 2, 'a' => 1], Arr::swap(['a' => 1, 'b' => 2, 'c' => 3], 'c', 'a'), 'swap map');
    }

    public function test_swap_non_existing_key1(): void
    {
        $this->expectExceptionMessage('Key: 0 does not exist.');
        $this->expectException(InvalidKeyException::class);
        Arr::swap([], 0, 1);
    }

    public function test_swap_non_existing_key2(): void
    {
        $this->expectExceptionMessage('Key: 1 does not exist.');
        $this->expectException(InvalidKeyException::class);
        Arr::swap([1], 0, 1);
    }

    public function test_symDiff(): void
    {
        self::assertSame([1], Arr::symDiff([], [1]), 'empty array1');
        self::assertSame([1], Arr::symDiff([1], []), 'empty array2');
        self::assertSame([], Arr::symDiff([1, 2], [2, 1]), 'same but not same order');
        self::assertSame([1, 3], Arr::symDiff([1, 2], [2, 3]), 'basic usage');
        self::assertSame(
            [[1], [3]],
            Arr::symDiff([[1], [2]], [[2], [3]], fn($a, $b) => $a[0] <=> $b[0]),
            'use by callback',
        );
    }

    public function test_symDiff_cannot_use_map_on_iterable1(): void
    {
        $this->expectExceptionMessage('$iterable1 must be a list, map given.');
        $this->expectException(TypeMismatchException::class);
        Arr::symDiff(['a' => 1], [1]);
    }

    public function test_symDiff_cannot_use_map_on_iterable2(): void
    {
        $this->expectExceptionMessage('$iterable2 must be a list, map given.');
        $this->expectException(TypeMismatchException::class);
        Arr::symDiff([1], ['a' => 1]);
    }

    public function test_takeEvery(): void
    {
        self::assertSame([], Arr::takeEvery([], 1), 'empty');
        self::assertSame([1, 2, 3], Arr::takeEvery([1, 2, 3], 1), 'take every 1st');
        self::assertSame([2, 4], Arr::takeEvery([1, 2, 3, 4, 5], 2), 'take every 2nd');
        self::assertSame([3, 6], Arr::takeEvery(range(1, 7), 3), 'take every 3rd');
        self::assertSame(['b' => 2], Arr::takeEvery(['a' => 1, 'b' => 2], 2), 'assoc');
    }

    public function test_takeEvery_zero_nth(): void
    {
        $this->expectExceptionMessage('Expected: $nth >= 1. Got: 0.');
        $this->expectException(InvalidArgumentException::class);
        Arr::takeEvery([], 0);
    }

    public function test_takeFirst(): void
    {
        // empty
        self::assertSame([], Arr::takeFirst([], 1));

        // zero take
        self::assertSame([], Arr::takeFirst([2, 3, 4], 0));

        // list
        self::assertSame([2, 3], Arr::takeFirst([2, 3, 4], 2));

        // assoc
        self::assertSame(['b' => 1], Arr::takeFirst(['b' => 1, 'a' => 3, 'c' => 2], 1));

        // amount over count
        self::assertSame([], Arr::takeFirst([], 100));
    }

    public function test_takeFirst_fail_on_negative_amount(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Expected: $amount >= 0. Got: -1.');
        Arr::takeFirst(['a' => 1], -1);
    }

    public function test_takeIf(): void
    {
        // list: removes ones with condition
        self::assertSame([''], Arr::takeIf([null, ''], static fn($v) => $v === ''));

        // assoc: removes ones with condition
        self::assertSame(['b' => ''], Arr::takeIf(['a' => null, 'b' => '', 'c' => null], static fn($v) => $v !== null));

        // reindex: true
        self::assertSame([''], Arr::takeIf([null, ''], static fn($v) => $v === '', reindex: true));

        // reindex: false
        self::assertSame([1 => ''], Arr::takeIf([null, ''], static fn($v) => $v === '', reindex: false));
    }

    public function test_takeInstanceOf(): void
    {
        // Test with empty array
        self::assertSame([], Arr::takeInstanceOf([], stdClass::class), 'empty array');

        // Create test objects
        $obj1 = new stdClass();
        $obj2 = new DateTime();
        $obj3 = new stdClass();

        // Test with list - should filter to only stdClass instances
        $list = [1, $obj1, 'string', $obj2, $obj3, null];
        $result = Arr::takeInstanceOf($list, stdClass::class);
        self::assertSame([$obj1, $obj3], $result, 'list with mixed types');

        // Test with map - should preserve keys by default
        $map = ['a' => 1, 'b' => $obj1, 'c' => 'string', 'd' => $obj2, 'e' => $obj3];
        $result = Arr::takeInstanceOf($map, stdClass::class);
        self::assertSame(['b' => $obj1, 'e' => $obj3], $result, 'map with mixed types');

        // Test reindex: true - should reindex even for maps
        $result = Arr::takeInstanceOf($map, stdClass::class, reindex: true);
        self::assertSame([$obj1, $obj3], $result, 'map with reindex true');

        // Test reindex: false - should preserve keys even for lists
        $result = Arr::takeInstanceOf($list, stdClass::class, reindex: false);
        self::assertSame([1 => $obj1, 4 => $obj3], $result, 'list with reindex false');

        // Test with DateTime class
        $result = Arr::takeInstanceOf($list, DateTime::class);
        self::assertSame([$obj2], $result, 'filter for DateTime class');

        // Test with no matching instances
        $result = Arr::takeInstanceOf([1, 'string', null], stdClass::class);
        self::assertSame([], $result, 'no matching instances');

        // Test with all matching instances
        $allObjects = [$obj1, $obj3];
        $result = Arr::takeInstanceOf($allObjects, stdClass::class);
        self::assertSame($allObjects, $result, 'all matching instances');
    }

    public function test_takeInstanceOf_fail_on_invalid_class(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Class: "NonExistentClass" does not exist.');
        Arr::takeInstanceOf([1, 2, 3], 'NonExistentClass');
    }

    public function test_takeKeys(): void
    {
        self::assertSame([], Arr::takeKeys([], []), 'empty');
        self::assertSame([2], Arr::takeKeys([1, 2, 3], [1]), 'with list array');
        self::assertSame(['a' => 1, 'b' => 2], Arr::takeKeys(['a' => 1, 'b' => 2, 'c' => 3], ['a', 'b']), 'with assoc array');
        self::assertSame(['c' => 3, 'b' => 2], Arr::takeKeys(['a' => 1, 'b' => 2, 'c' => 3], ['c', 'b']), 'different order of keys');
        self::assertSame(['c' => 3, 'b' => 2], Arr::takeKeys(['a' => 1, 'b' => 2, 'c' => 3], ['x' => 'c', 'b']), 'different order of keys');
        self::assertSame([1], Arr::takeKeys([1, 2, 3], [0, 300], false), 'safe: false with list');
        self::assertSame(['a' => 1], Arr::takeKeys(['a' => 1, 'b' => 2], ['a', 'z'], false), 'safe: false with map');
    }

    public function test_takeKeys_safe_on_non_existing_keys(): void
    {
        $this->expectException(MissingKeyException::class);
        $this->expectExceptionMessage("Keys: [1, 2, 'b']");
        self::assertSame([], Arr::takeKeys([], [1, 2, 'b']));
    }

    public function test_takeLast(): void
    {
        self::assertSame([], Arr::takeLast([], 1), 'empty');
        self::assertSame([], Arr::takeLast([2, 3, 4], 0), 'zero take');
        self::assertSame([3, 4], Arr::takeLast([2, 3, 4], 2), 'list');
        self::assertSame([1, 2], Arr::takeLast([1, 2], 3), 'overflow amount');
        self::assertSame(['c' => 2], Arr::takeLast(['b' => 1, 'a' => 3, 'c' => 2], 1), 'assoc');
    }

    public function test_takeLast_fail_on_negative_amount(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Expected: $amount >= 0. Got: -1.');
        Arr::takeLast(['a' => 1], -1);
    }

    public function test_takeUntil(): void
    {
        // empty
        self::assertSame([], Arr::takeUntil([], static fn($v) => false));

        // list
        $list = Arr::takeUntil([1, 1, 3, 2], static fn($v) => $v > 2);
        self::assertSame([1, 1], $list);

        // assoc
        $assoc = Arr::takeUntil(['b' => 1, 'a' => 3, 'c' => 2], static fn($v) => $v > 2);
        self::assertSame(['b' => 1], $assoc);

        // all false
        $assoc = Arr::takeUntil(['b' => 1, 'a' => 3, 'c' => 2], static fn($v) => false);
        self::assertSame(['b' => 1, 'a' => 3, 'c' => 2], $assoc);

        // all true
        $assoc = Arr::takeUntil(['b' => 1, 'a' => 3, 'c' => 2], static fn($v) => true);
        self::assertSame([], $assoc);
    }

    public function test_takeWhile(): void
    {
        // empty
        self::assertSame([], Arr::takeUntil([], static fn($v) => false));

        // list
        self::assertSame([1, 1], Arr::takeWhile([1, 1, 3, 2], static fn($v) => $v <= 2));

        // assoc
        $assoc = Arr::takeWhile(['b' => 1, 'a' => 3, 'c' => 4], static fn($v) => $v < 4);
        self::assertSame(['b' => 1, 'a' => 3], $assoc);

        // all false
        $assoc = Arr::takeWhile(['b' => 1, 'a' => 3, 'c' => 2], static fn($v) => false);
        self::assertSame([], $assoc);

        // all true
        $assoc = Arr::takeWhile(['b' => 1, 'a' => 3, 'c' => 2], static fn($v) => true);
        self::assertSame(['b' => 1, 'a' => 3, 'c' => 2], $assoc);
    }

    public function test_toUrlQuery(): void
    {
        // basic usage
        self::assertSame("a=1&b=2", Arr::toUrlQuery(['a' => 1, 'b' => 2]));

        // with namespace
        self::assertSame(urlencode('t[a]') . '=1', Arr::toUrlQuery(['a' => 1], 't'));

        // with space as %20
        self::assertSame(urlencode('t[a]') . '=a%20b', Arr::toUrlQuery(['a' => 'a b'], 't'));
    }

    public function test_unique(): void
    {
        // empty
        self::assertSame([], Arr::unique([]));

        // list
        self::assertSame([1, 2], Arr::unique([1, 1, 2, 2]));

        // assoc
        self::assertSame(['a' => 1, 'b' => 2], Arr::unique(['a' => 1, 'b' => 2, 'c' => 2]));

        // don't convert to string like array_unique
        $values = ['3', 3, null, '', 0, true, false];
        self::assertSame($values, Arr::unique(Arr::repeat($values, 2)));

        // reindex: true
        self::assertSame([1, 2], Arr::unique(['a' => 1, 'b' => 2, 'c' => 2], reindex: true));

        // reindex: false
        self::assertSame(['a' => 1, 'b' => 2], Arr::unique(['a' => 1, 'b' => 2, 'c' => 2], reindex: false));

        // empty with callback
        /** @var array<int> $array */
        $array = [];
        self::assertSame([], Arr::unique($array, static fn() => 1));

        // list with callback
        self::assertSame([1, 2], Arr::unique([1, 2, 3, 4], static fn($v) => $v % 2));

        // assoc with callback
        self::assertSame(['a' => 1, 'b' => 2], Arr::unique(['a' => 1, 'b' => 2, 'c' => 2], static fn($v) => $v % 2));

        // make sure condition does not convert result to string like array_unique
        self::assertSame($values, Arr::unique(Arr::repeat($values, 2), static fn($v) => $v));
    }

    public function test_values(): void
    {
        self::assertSame([], Arr::values([]), 'empty');
        self::assertSame([1, 1, 2], Arr::values([1, 1, 2]),'list');
        self::assertSame([1, 2], Arr::values(['a' => 1, 'b' => 2]), 'assoc');
        self::assertSame([1, 2], Arr::values([1 => 1, 0 => 2]), 'unordered');
    }

    public function test_withDefaults(): void
    {
        self::assertSame([], Arr::withDefaults([], []), 'empty');
        self::assertSame([1], Arr::withDefaults([1], []), 'empty defaults');
        self::assertSame([1, 2], Arr::withDefaults([], [1, 2]), 'empty iterable');
        self::assertSame([1, 3], Arr::withDefaults([1], [2, 3]), 'first element exists');
        self::assertSame(['a' => 1], Arr::withDefaults([], ['a' => 1]), 'mix list and map');
        self::assertSame(['a' => 1], Arr::withDefaults(['a' => 1], ['a' => 2]), 'map key exists');
        self::assertSame([1, 'a' => 2], Arr::withDefaults([1], ['a' => 2]), 'mix list and map');
    }

    public function test_zip(): void
    {
        self::assertSame([], Arr::zip([]), 'empty no 2+ args');
        self::assertSame([], Arr::zip([], []), 'empty args');
        self::assertSame([[1]], Arr::zip([1]), 'no list args');
        self::assertSame([[1, 2]], Arr::zip([1], [2, 3]), '2nd arg has more elements');
        self::assertSame([[1, null, 5], [2, null, null]], Arr::zip([1, 2], [], [5]), 'list uneven');
        self::assertSame([[1, 3, 5], [2, 4, 6]], Arr::zip([1, 2], [3, 4], [5, 6]), 'list even');
    }

    public function test_zip_no_arg(): void
    {
        $this->expectExceptionMessage('Arr::zip() expects at least 1 argument.');
        $this->expectException(InvalidArgumentException::class);
        Arr::zip();
    }

    public function test_zip_map_first_arg(): void
    {
        $this->expectExceptionMessage('Argument #1 must be a list, map given.');
        $this->expectException(TypeMismatchException::class);
        Arr::zip(['a' => 1]);
    }

    public function test_zip_map_2nd_arg(): void
    {
        $this->expectExceptionMessage('Argument #2 must be a list, map given.');
        $this->expectException(TypeMismatchException::class);
        Arr::zip([1], ['a' => 1]);
    }

    public function test_setDefaultRandomizer(): void
    {
        $randomizer = new Randomizer(new FixedNumEngine());
        Arr::setDefaultRandomizer($randomizer);
        self::assertSame($randomizer, Arr::getDefaultRandomizer());
    }

    public function test_getDefaultRandomizer(): void
    {
        self::assertInstanceOf(Randomizer::class, Arr::getDefaultRandomizer());
    }
}
