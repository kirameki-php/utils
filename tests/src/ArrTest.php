<?php declare(strict_types=1);

namespace Tests\Kirameki\Utils;

use DivisionByZeroError;
use Exception;
use InvalidArgumentException;
use Kirameki\Utils\Arr;
use Kirameki\Utils\Iter;
use Kirameki\Utils\Str;
use Kirameki\Utils\Support\Miss;
use RuntimeException;
use stdClass;
use TypeError;

class ArrTest extends TestCase
{
    public function test_at(): void
    {
        self::assertEquals(1, Arr::at([1, 2, 3], 0));
        self::assertEquals(2, Arr::at([1, 2, 3], 1));
        self::assertEquals(3, Arr::at([1, 2, 3], -1));

        self::assertEquals(1, Arr::at(['a' => 1, 'b' => 2, 'c' => 3], 0));
        self::assertEquals(2, Arr::at(['a' => 1, 'b' => 2, 'c' => 3], 1));
        self::assertEquals(3, Arr::at(['a' => 1, 'b' => 2, 'c' => 3], -1));
    }

    public function test_atOr(): void
    {
        // empty
        self::assertEquals('x', Arr::atOr([], 0, 'x'));

        // not found and return null
        self::assertEquals(null, Arr::atOr([1, 2, 3], 3, null));
        self::assertEquals(null, Arr::atOr([1, 2, 3], -4, null));

        // return existing value
        self::assertEquals(1, Arr::atOr([1, 2, 3], 3, 1));

        // miss with object
        $miss = Miss::instance();
        self::assertEquals($miss, Arr::atOr([1, 2, 3], 3, $miss));

        // hit
        self::assertEquals(3, Arr::atOr([1, 2, 3], 2, null));

        // hit reverse
        self::assertEquals(3, Arr::atOr([1, 2, 3], -1, null));
    }

    public function test_atOrFail_on_empty(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Index out of bounds. position: 0');
        self::assertEquals(null, Arr::atOrFail([], 0));
    }

    public function test_atOrFail_missing_index(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Index out of bounds. position: 5');
        self::assertEquals(null, Arr::atOrFail([1, 2, 3], 5));
    }

    public function test_average(): void
    {
        // empty
        self::assertEquals(0, Arr::average([]));

        // don't allow empty but pass
        self::assertEquals(1.5, Arr::average([1, 2], false));

        // only one element
        self::assertEquals(1, Arr::average([1]));

        // standard average (float)
        self::assertEquals(1.5, Arr::average([1, 2]));

        // standard average (int)
        self::assertEquals(2, Arr::average([1, 2, 3]));

        // standard average (all zeros)
        self::assertEquals(0, Arr::average([0, 0, 0]));

        // average from map
        self::assertEquals(2, Arr::average(['a' => 1, 'b' => 2, 'c' => 3]));
    }

    /**
     * @noinspection PhpExpressionResultUnusedInspection
     */
    public function test_average_not_empty(): void
    {
        $this->expectException(DivisionByZeroError::class);
        Arr::average([], false);
    }

    public function test_chunk(): void
    {
        // empty
        self::assertEmpty(Arr::chunk([], 1));

        $chunked = Arr::chunk([1, 2, 3], 2);
        self::assertEquals([[1, 2], [3]], $chunked);

        // size larger than items -> returns everything
        $chunked = Arr::chunk([1, 2, 3], 4);
        self::assertEquals([[1, 2, 3]], $chunked);

        // size larger than items -> returns everything
        $chunked = Arr::chunk(['a' => 1, 'b' => 2, 'c' => 3], 4);
        self::assertEquals([['a' => 1, 'b' => 2, 'c' => 3]], $chunked);

        // force reindex: false on list
        $chunked = Arr::chunk([1, 2, 3], 2, reindex: false);
        self::assertEquals([[0 => 1, 1 => 2], [2 => 3]], $chunked);

        // force reindex: false on assoc
        $chunked = Arr::chunk(['a' => 1, 'b' => 2, 'c' => 3], 2);
        self::assertEquals([['a' => 1, 'b' => 2], ['c' => 3]], $chunked);

        // force reindex: false on list
        $chunked = Arr::chunk([1, 2, 3], 2, reindex: true);
        self::assertEquals([[1, 2], [3]], $chunked);

        // force reindex: false on assoc
        $chunked = Arr::chunk(['a' => 1, 'b' => 2, 'c' => 3], 2, reindex: true);
        self::assertEquals([[1, 2], [3]], $chunked);

    }

    public function test_chunk_invalid_size(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Expected a positive integer. Got: 0');
        Arr::chunk([1], 0);
    }

    public function test_coalesce(): void
    {
        // empty
        self::assertNull(Arr::coalesce([]));

        // skip first
        self::assertEquals(0, Arr::coalesce([null, 0, 1]));

        // zero is valid
        self::assertEquals(0, Arr::coalesce([0, null, 1]));

        // empty string is valid
        self::assertEquals('', Arr::coalesce(['', null, 1]));

        // empty array is valid
        self::assertEquals([], Arr::coalesce([[], null, 1]));

        // empty array after null is valid
        self::assertEquals([], Arr::coalesce([null, [], 1]));

        // skip all nulls
        self::assertEquals(1, Arr::coalesce([null, null, 1]));

        // everything skipped and returns null
        self::assertEquals(null, Arr::coalesce([null, null]));
    }

    public function test_coalesceOrFail(): void
    {
        // skip first
        self::assertEquals(0, Arr::coalesceOrFail([null, 0, 1]));

        // zero is valid
        self::assertEquals(0, Arr::coalesceOrFail([0, null, 1]));

        // skip all nulls
        self::assertEquals(1, Arr::coalesceOrFail([null, null, 1]));

        // empty string is valid
        self::assertEquals('', Arr::coalesceOrFail(['', null, 1]));

        // empty array is valid
        self::assertEquals([], Arr::coalesceOrFail([[], null, 1]));
    }

    public function test_coalesceOrFail_empty(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Non-null value could not be found.');
        Arr::coalesceOrFail([]);
    }

    public function test_coalesceOrFail_only_null(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Non-null value could not be found.');
        Arr::coalesceOrFail([null]);
    }

    public function test_compact(): void
    {
        // empty
        self::assertEquals([], Arr::compact([]));

        // list: no nulls
        self::assertEquals([1, 2], Arr::compact([1, 2]));

        // list: all nulls
        self::assertEquals([], Arr::compact([null, null]));

        // assoc: removes nulls
        self::assertEquals(['b' => 1, 'c' => 2], Arr::compact(['a' => null, 'b' => 1, 'c' => 2, 'd' => null]));

        // assoc: no nulls
        self::assertEquals(['a' => 1, 'b' => 2], Arr::compact(['a' => 1, 'b' => 2]));

        // assoc: all nulls
        self::assertEquals([], Arr::compact(['a' => null, 'b' => null]));

        // depth = 1
        $compacted = Arr::compact(['a' => ['b' => null], 'b' => null]);
        self::assertEquals(['a' => ['b' => null]], $compacted);

        // depth = INT_MAX
        $compacted = Arr::compact(['a' => ['b' => ['c' => null]], 'b' => null], PHP_INT_MAX);
        self::assertEquals(['a' => ['b' => []]], $compacted);

        // list: removes nulls (reindex: null (auto-detect))
        self::assertEquals([1, 2], Arr::compact([1, null, 2]));

        // reindex: true with list
        self::assertEquals([1, 2], Arr::compact([1, null, null, 2], 1, true));

        // reindex: true with map
        self::assertEquals([1], Arr::compact(['a' => null, 'b' => null, 'c' => 1], 1, true));

        // reindex: false with list
        self::assertEquals([1, 2 => 2], Arr::compact([1, null, 2], 1, false));

        // reindex: false with map
        self::assertEquals(['c' => 1], Arr::compact(['a' => null, 'b' => null, 'c' => 1], 1, false));
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

        // assoc: compared with value
        $assoc = ['a' => 1];
        self::assertTrue(Arr::contains($assoc, 1));
        self::assertFalse(Arr::contains($assoc, ['a' => 1]));
        self::assertFalse(Arr::contains($assoc, ['a']));
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

    public function test_count(): void
    {
        // empty
        self::assertEquals(0, Arr::count([]));

        // count default
        self::assertEquals(2, Arr::count([1, 2]));

        // count assoc
        self::assertEquals(2, Arr::count(['a' => 1, 'b' => 2]));
    }

    public function test_countBy(): void
    {
        // empty
        self::assertEquals(0, Arr::countBy([], static fn() => true));

        // condition success
        self::assertEquals(2, Arr::countBy([1, 2], static fn() => true));

        // condition fails
        self::assertEquals(0, Arr::countBy([1, 2], static fn() => false));

        // condition partially success
        self::assertEquals(2, Arr::countBy([1, 2, 3], static fn($v) => $v > 1));

        // condition checked with key
        self::assertEquals(1, Arr::countBy(['a' => 1, 'b' => 2], static fn($v,$k) => $k === 'a'));
    }

    public function test_diff(): void
    {
        // empty array1
        self::assertEquals([], Arr::diff([], [1]));

        // empty array2
        self::assertEquals([1, 'a' => 1], Arr::diff([1, 'a' => 1], []));

        // array1 is list (re-indexed automatically)
        self::assertEquals([2], Arr::diff([1, 2], [1, 3]));

        // array1 is assoc
        self::assertEquals(['b' => 2], Arr::diff(['a' => 1, 'b' => 2], ['b' => 1, 'c' => 3]));

        // same values in list
        self::assertEquals([], Arr::diff([1, 1], [1]));

        // same values in assoc
        self::assertEquals([], Arr::diff([1, 'a' => 1, 'b' => 1], [1]));

        // array1 is list (re-indexed automatically)
        self::assertEquals([2], Arr::diff([1, 2], [1, 3]));

        // array1 is assoc
        self::assertEquals(['b' => 2], Arr::diff(['a' => 1, 'b' => 2], ['b' => 1, 'c' => 3]));

        // reindex: true on list
        self::assertEquals([2], Arr::diff([1, 2], [1, 3], reindex: true));

        // reindex: true on assoc
        self::assertEquals([2], Arr::diff(['a' => 1, 'b' => 2], ['b' => 1, 'c' => 3], reindex: true));

        // reindex: false on list
        self::assertEquals([1 => 2], Arr::diff([1, 2], [1, 3], reindex: false));

        // reindex: false on assoc
        self::assertEquals(['b' => 2], Arr::diff(['a' => 1, 'b' => 2], ['b' => 1, 'c' => 3], reindex: false));
    }

    public function test_diffKeys(): void
    {
        // empty array1
        self::assertEquals([], Arr::diffKeys([], [1]));

        // empty array2
        self::assertEquals([1], Arr::diffKeys([1], []));

        // same values in list
        self::assertEquals([2], Arr::diffKeys([1, 2], [3]));

        // unique keys but has same values
        self::assertEquals(['a' => 1, 'b' => 2], Arr::diffKeys(['a' => 1, 'b' => 2], ['c' => 2]));

        // retain only on left side
        self::assertEquals(['b' => 2], Arr::diffKeys(['a' => 1, 'b' => 2], ['a' => 2, 'c' => 3]));

        // reindex: true on list
        self::assertEquals([2], Arr::diffKeys([1, 2], [3], reindex: true));

        // reindex: true on assoc
        self::assertEquals([2], Arr::diffKeys(['a' => 1, 'b' => 2], ['a' => 3], reindex: true));

        // reindex: false on list
        self::assertEquals([1 => 2], Arr::diffKeys([1, 2], [3], reindex: false));

        // reindex: false on assoc
        self::assertEquals(['b' => 2], Arr::diffKeys(['a' => 1, 'b' => 2], ['a' => 3], reindex: false));
    }

    public function test_drop(): void
    {
        // empty
        self::assertEquals([], Arr::drop([], 1));

        // drop nothing
        self::assertEquals([1], Arr::drop([1], 0));

        // drop list
        self::assertEquals([1, 2], Arr::drop([1, 1, 2], 1));

        // drop assoc
        self::assertEquals(['b' => 2], Arr::drop(['a' => 1, 'b' => 2], 1));

        // over value
        self::assertEquals([], Arr::drop(['a' => 1, 'b' => 2], 3));
    }

    public function test_drop_fail_on_negative_amount(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Expected a value greater than or equal to 0. Got: -1');
        Arr::drop(['a' => 1], -1);
    }

    public function test_dropUntil(): void
    {
        // empty
        self::assertEquals([], Arr::dropUntil([], static fn($v) => $v >= 3));

        // list
        self::assertEquals([3, 4], Arr::dropUntil([1, 2, 3, 4], static fn($v) => $v >= 3));

        // look at value
        self::assertEquals(
            ['c' => 3, 'd' => 4],
            Arr::dropUntil(['b' => 2, 'c' => 3, 'd' => 4], static fn($v, $k) => $v >= 3)
        );

        // look at key
        self::assertEquals(
            ['c' => 3, 'd' => 4],
            Arr::dropUntil(['a' => 1, 'b' => 2, 'c' => 3, 'd' => 4], static fn($v, $k) => $k === 'c')
        );

        // reindex: true on list
        self::assertEquals(
            [3, 4],
            Arr::dropUntil([1, 2, 3, 4], static fn($v) => $v >= 3, reindex: true)
        );

        // reindex: true on assoc
        self::assertEquals(
            [4],
            Arr::dropUntil(['a' => 1, 'b' => 4], static fn($v) => $v >= 3, reindex: true)
        );

        // reindex: false on list
        self::assertEquals(
            [2 => 3, 3 => 4],
            Arr::dropUntil([1, 2, 3, 4], static fn($v) => $v >= 3, reindex: false)
        );

        // reindex: false on assoc
        self::assertEquals(
            ['b' => 4],
            Arr::dropUntil(['a' => 1, 'b' => 4], static fn($v) => $v >= 3, reindex: false)
        );

        // drop until null does not work
        $this->expectException(TypeError::class);
        $this->expectExceptionMessage(Iter::class . '::verify(): Return value must be of type bool, null returned');
        /** @phpstan-ignore-next-line */
        Arr::dropUntil([1], static fn(int $v, int $k) => null);
    }

    public function test_dropWhile(): void
    {

        // empty
        self::assertEquals([], Arr::dropWhile([], static fn($v) => $v <= 3));

        // list
        self::assertEquals([3, 4], Arr::dropWhile([1, 2, 3, 4], static fn($v) => $v < 3));

        // look at value
        self::assertEquals(
            ['c' => 3, 'd' => 4],
            Arr::dropWhile(['b' => 2, 'c' => 3, 'd' => 4], static fn($v, $k) => $v < 3)
        );

        // look at key
        self::assertEquals(
            ['c' => 3, 'd' => 4],
            Arr::dropWhile(['a' => 1, 'b' => 2, 'c' => 3, 'd' => 4], static fn($v, $k) => $k !== 'c')
        );

        // reindex: true on list
        self::assertEquals(
            [3, 4],
            Arr::dropWhile([1, 2, 3, 4], static fn($v) => $v < 3, reindex: true)
        );

        // reindex: true on assoc
        self::assertEquals(
            [4],
            Arr::dropWhile(['a' => 1, 'b' => 4], static fn($v) => $v < 3, reindex: true)
        );

        // reindex: false on list
        self::assertEquals(
            [2 => 3, 3 => 4],
            Arr::dropWhile([1, 2, 3, 4], static fn($v) => $v < 3, reindex: false)
        );

        // reindex: false on assoc
        self::assertEquals(
            ['b' => 4],
            Arr::dropWhile(['a' => 1, 'b' => 4], static fn($v) => $v <= 3, reindex: false)
        );

        // drop while null does not work
        $this->expectException(TypeError::class);
        $this->expectExceptionMessage(Iter::class . '::verify(): Return value must be of type bool, null returned');
        /** @phpstan-ignore-next-line */
        Arr::dropWhile([1], static fn(int $v, int $k) => null);
    }

    public function test_duplicates(): void
    {
        // empty
        self::assertEquals([], Arr::duplicates([]));

        // null
        self::assertEquals([null], Arr::duplicates([null, null]));

        // no dupes
        self::assertEquals([], Arr::duplicates(['a' => 1, 'b' => 2]));

        // on list
        self::assertEquals([4, 'a'], Arr::duplicates([5, 6, 4, 4, 'a', 'a', 'b']));

        // on assoc
        self::assertEquals([1], Arr::duplicates(['a' => 1, 'b' => 1, 'c' => 1, 'd' => 2]));

        // same object instance
        $instance = new stdClass();
        self::assertEquals([$instance], Arr::duplicates([$instance, $instance]));

        // different object instance
        self::assertEquals([], Arr::duplicates([new stdClass(), new stdClass()]));
    }

    public function test_each(): void
    {
        // empty
        Arr::each([], static fn () => throw new Exception());

        // list
        Arr::each(['a', 'b'], static function (string $v, int $k) {
            match ($k) {
                0 => self::assertEquals('a', $v),
                1 => self::assertEquals('b', $v),
                default => throw new Exception(''),
            };
        });

        // assoc
        Arr::each(['a' => 1, 'b' => 2], static function ($v, $k) {
            match ($k) {
                'a' => self::assertEquals(['a' => 1], [$k => $v]),
                'b' => self::assertEquals(['b' => 2], [$k => $v]),
                default => throw new Exception(''),
            };
        });
    }

    public function test_except(): void
    {
        // empty array
        self::assertEquals([], Arr::except([], ['a']));

        // empty except
        self::assertEquals(['a' => 1], Arr::except(['a' => 1], []));

        // expect key (int)
        self::assertEquals([2], Arr::except([1, 2, 3], [0, 2]));

        // expect key (string)
        self::assertEquals(['b' => 2], Arr::except(['a' => 1, 'b' => 2], ['a']));

        // $keys contains non-existing key
        self::assertEquals(['b' => 2], Arr::except(['a' => 1, 'b' => 2], ['a', 'c']));

        // reindex: true on list
        self::assertEquals([2], Arr::except([1, 2, 3], [0, 2], reindex: true));

        // reindex: true on assoc
        self::assertEquals([2], Arr::except(['a' => 1, 'b' => 2], ['a'], reindex: true));

        // reindex: false on list
        self::assertEquals([1 => 2], Arr::except([1, 2, 3], [0, 2], reindex: false));

        // reindex: false on assoc
        self::assertEquals(['b' => 2], Arr::except(['a' => 1, 'b' => 2], ['a'], reindex: false));
    }

    public function test_filter(): void
    {
        // list: removes ones with condition
        self::assertEquals([''], Arr::filter([null, ''], static fn($v) => $v === ''));

        // assoc: removes ones with condition
        self::assertEquals(['b' => ''], Arr::filter(['a' => null, 'b' => '', 'c' => null], static fn($v) => $v !== null));

        // with first class closure syntax
        self::assertEquals([], Arr::filter([null, ''], Str::isNotBlank(...)));

        // reindex: true
        self::assertEquals([''], Arr::filter([null, ''], static fn($v) => $v === '', reindex: true));

        // reindex: false
        self::assertEquals([1 => ''], Arr::filter([null, ''], static fn($v) => $v === '', reindex: false));
    }

    public function test_first(): void
    {
        // empty
        self::assertEquals(null, Arr::first([]));

        // no match
        self::assertEquals(null, Arr::first([1,2], fn(int $i) => $i > 2));

        // one element
        self::assertEquals(1, Arr::first([1], fn($v) => true));

        // list
        self::assertEquals(10, Arr::first([10, 20]));
        self::assertEquals(20, Arr::first([10, 20], fn($v, $k) => $k === 1));
        self::assertEquals(20, Arr::first([10, 20], fn($v, $k) => $v === 20));

        // assoc
        self::assertEquals(10, Arr::first(['a' => 10, 'b' => 20, 'c' => 30]));
        self::assertEquals(10, Arr::first(['a' => 10, 'b' => 20, 'c' => 30], fn($v, $k) => $k === 'a'));
        self::assertEquals(20, Arr::first(['a' => 10, 'b' => 20, 'c' => 30], fn($v, $k) => $k === 'b'));
    }

    public function test_firstIndex(): void
    {
        // empty
        self::assertEquals(null, Arr::firstIndex([], fn($v, $k) => true));

        // list
        self::assertEquals(2, Arr::firstIndex([10, 20, 20, 30], fn($v, $k) => $k === 2));
        self::assertEquals(1, Arr::firstIndex([10, 20, 20, 30], fn($v, $k) => $v === 20));
        self::assertEquals(null, Arr::firstIndex([10, 20, 20, 30], fn() => false));

        // assoc
        self::assertEquals(1, Arr::firstIndex(['a' => 1, 'b' => 2, 'c' => 3], fn($v, $k) => $v === 2));
        self::assertEquals(2, Arr::firstIndex(['a' => 1, 'b' => 2, 'c' => 3], fn($v, $k) => $k === 'c'));
        self::assertEquals(1, Arr::firstIndex(['a' => 1, 'b' => 2, 'c' => 3], fn($v, $k) => $v > 1));
        self::assertEquals(null, Arr::firstIndex(['a' => 1, 'b' => 2, 'c' => 3], fn($v, $k) => $v > 10));
    }

    public function test_firstKey(): void
    {
        // empty
        self::assertEquals(null, Arr::firstKey([], fn($v, $k) => true));

        // list
        self::assertEquals(null, Arr::firstKey([10, 20, 20, 30], fn() => false));
        self::assertEquals(1, Arr::firstKey([10, 20, 30], fn($v, $k) => $v === 20));
        self::assertEquals(2, Arr::firstKey([10, 20, 30], fn($v, $k) => $k === 2));

        // assoc
        self::assertEquals(null, Arr::firstKey(['a' => 1, 'b' => 2, 'c' => 3], fn($v, $k) => $v > 10));
        self::assertEquals('b', Arr::firstKey(['a' => 1, 'b' => 2, 'c' => 3], fn($v, $k) => $v === 2));
        self::assertEquals('c', Arr::firstKey(['a' => 1, 'b' => 2, 'c' => 3], fn($v, $k) => $k === 'c'));
    }

    public function test_firstOr(): void
    {
        // empty
        self::assertEquals(INF, Arr::firstOr([], INF));

        // list
        self::assertEquals(1, Arr::firstOr([1, 2], INF));
        self::assertEquals(2, Arr::firstOr([1, 2], INF, fn($v, $k) => $k === 1));
        self::assertEquals(2, Arr::firstOr([1, 2], INF, fn($v, $k) => $v === 2));
        self::assertEquals(INF, Arr::firstOr([1, 2], INF, fn() => false));

        // assoc
        self::assertEquals(1, Arr::firstOr(['a' => 1, 'b' => 2], INF));
        self::assertEquals(2, Arr::firstOr(['a' => 1, 'b' => 2], INF, fn($v, $k) => $k === 'b'));
        self::assertEquals(2, Arr::firstOr(['a' => 1, 'b' => 2], INF, fn($v, $k) => $v === 2));
        self::assertEquals(INF, Arr::firstOr(['a' => 1, 'b' => 2], INF, fn() => false));
    }

    public function test_firstOrFail(): void
    {
        // list
        self::assertEquals(1, Arr::firstOrFail([1, 2]));
        self::assertEquals(2, Arr::firstOrFail([1, 2], fn($v, $k) => $k === 1));
        self::assertEquals(2, Arr::firstOrFail([1, 2], fn($v, $k) => $v === 2));

        // assoc
        self::assertEquals(1, Arr::firstOrFail(['a' => 1, 'b' => 2]));
        self::assertEquals(2, Arr::firstOrFail(['a' => 1, 'b' => 2], fn($v, $k) => $k === 'b'));
        self::assertEquals(2, Arr::firstOrFail(['a' => 1, 'b' => 2], fn($v, $k) => $v === 2));
    }

    public function test_firstOrFail_empty(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Iterable must contain at least one element.');
        Arr::firstOrFail([]);
    }

    public function test_firstOrFail_bad_condition(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Failed to find matching condition.');
        Arr::firstOrFail([1,2], fn(int $i) => $i > 2);
    }

    /**
    public function test_flatMap(): void
    {
        $seq = $this->seq([1, 2]);
        self::assertEquals([1, -1, 2, -2], $seq->flatMap(fn($i) => [$i, -$i])->toArray());

        $seq = $this->seq([['a'], ['b']]);
        self::assertEquals(['a', 'b'], $seq->flatMap(fn($a) => $a)->toArray());

        $seq = $this->seq([['a' => 1], [2], 2]);
        self::assertEquals([1, 2, 2], $seq->flatMap(fn($a) => $a)->toArray());
    }

    public function test_flatten(): void
    {
        // nothing to flatten
        $seq = $this->seq([1, 2]);
        self::assertEquals([1, 2], $seq->flatten()->toArray());

        // flatten only 1 as default
        $seq = $this->seq([[1, [2, 2]], 3]);
        self::assertEquals([1, [2, 2], 3], $seq->flatten()->toArray());

        // flatten more than 1
        $seq = $this->seq([['a' => 1], [1, [2, [3, 3], 2], 1]]);
        self::assertEquals([1, 1, 2, [3, 3], 2, 1], $seq->flatten(2)->toArray());

        // assoc info is lost
        $seq = $this->seq([['a'], 'b', ['c' => 'd']]);
        self::assertEquals(['a', 'b', 'd'], $seq->flatten()->toArray());
    }

    public function test_flatten_zero_depth(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Expected a positive integer. Got: 0');
        $seq = $this->seq([1, 2]);
        self::assertEquals([1, 2], $seq->flatten(0)->toArray());
    }

    public function test_flatten_negative_depth(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Expected a positive integer. Got: -1');
        $seq = $this->seq([1, 2]);
        self::assertEquals([1, 2], $seq->flatten(-1)->toArray());
    }

    public function test_flip(): void
    {
        $seq = $this->seq([1, 2]);
        self::assertEquals([1, 2], $seq->flip()->keys()->toArray());
        self::assertEquals([0, 1], $seq->flip()->values()->toArray());

        $seq = $this->seq(['a' => 'b', 'c' => 'd']);
        self::assertEquals(['b' => 'a', 'd' => 'c'], $seq->flip()->toArray());
    }

    public function test_flip_invalid_key_type(): void
    {
        $this->expectException(InvalidKeyException::class);
        $this->seq([true, false])->flip();
    }

    public function test_fold(): void
    {
        $reduced = $this->seq([])->fold(0, fn(int $i) => $i + 1);
        self::assertEquals(0, $reduced);

        $reduced = $this->seq(['a' => 1, 'b' => 2])->fold(new Collection(), static fn(Collection $c, int $i, string $k) => $c->set($k, $i * 2));
        self::assertEquals(['a' => 2, 'b' => 4], $reduced->toArray());

        $reduced = $this->seq(['a' => 1, 'b' => 2])->fold((object)[], static function ($c, $i, $k) {
            $c->$k = 0;
            return $c;
        });
        self::assertEquals(['a' => 0, 'b' => 0], (array) $reduced);

        $reduced = $this->seq([1, 2, 3])->fold(0, fn(int $c, $i, $k) => $c + $i);
        self::assertEquals(6, $reduced);
    }

    public function test_getIterator(): void
    {
        $iterator = $this->seq([1])->getIterator();
        self::assertEquals([1], iterator_to_array($iterator));
    }

    public function test_groupBy(): void
    {
        $seq = $this->seq([1, 2, 3, 4, 5, 6]);
        $grouped = $seq->groupBy(fn(int $n): int => $n % 3)->toArrayRecursive();
        self::assertEquals([[3, 6], [1, 4], [2, 5]], $grouped);

        $seq = $this->seq([
            ['id' => 1],
            ['id' => 2],
            ['id' => 1],
        ]);
        self::assertEquals([
            1 => [
                ['id' => 1],
                ['id' => 1]
            ],
            2 => [
                ['id' => 2]
            ]
        ], $seq->groupBy('id')->toArrayRecursive());
    }

    public function test_groupBy_missing_key(): void
    {
        $this->expectException(ErrorException::class);
        $this->expectExceptionMessage('Undefined array key "id"');
        $this->seq([['dummy' => 3]])->groupBy('id');
    }

    public function test_intersect(): void
    {
        $seq = $this->seq([1, 2, 3]);
        self::assertEquals([1], $seq->intersect([1])->toArray());

        $seq = $this->seq(['a' => 1, 'b' => 2, 'c' => 3]);
        self::assertEquals(['a' => 1], $seq->intersect([1])->toArray());

        $seq = $this->seq([]);
        self::assertEquals([], $seq->intersect([1])->toArray());
    }

    public function test_intersectKeys(): void
    {
        $seq = $this->seq([1, 2, 3]);
        self::assertEquals([1, 2], $seq->intersectKeys([1, 3])->toArray());

        $seq = $this->seq(['a' => 1, 'b' => 2, 'c' => 3]);
        self::assertEquals([], $seq->intersectKeys([1])->toArray());

        $seq = $this->seq(['a' => 1, 'b' => 2, 'c' => 3]);
        self::assertEquals(['a' => 1], $seq->intersectKeys(['a' => 2])->toArray());

        $seq = $this->seq([]);
        self::assertEquals([], $seq->intersectKeys(['a' => 1])->toArray());

        $seq = $this->seq(['a' => 1]);
        self::assertEquals([], $seq->intersectKeys([])->toArray());
    }

    public function test_isAssoc(): void
    {
        $seq = $this->seq([]);
        self::assertTrue($seq->isAssoc());

        $seq = $this->seq([1, 2]);
        self::assertFalse($seq->isAssoc());

        $seq = $this->seq(['a' => 1, 'b' => 2]);
        self::assertTrue($seq->isAssoc());
    }

    public function test_isEmpty(): void
    {
        $seq = $this->seq([]);
        self::assertTrue($seq->isEmpty());

        $seq = $this->seq([1, 2]);
        self::assertFalse($seq->isEmpty());

        $seq = $this->seq(['a' => 1, 'b' => 2]);
        self::assertFalse($seq->isEmpty());
    }

    public function test_isNotEmpty(): void
    {
        $seq = $this->seq([]);
        self::assertFalse($seq->isNotEmpty());

        $seq = $this->seq([1, 2]);
        self::assertTrue($seq->isNotEmpty());

        $seq = $this->seq(['a' => 1, 'b' => 2]);
        self::assertTrue($seq->isNotEmpty());
    }

    public function test_isList(): void
    {
        $seq = $this->seq([]);
        self::assertTrue($seq->isList());

        $seq = $this->seq([1, 2]);
        self::assertTrue($seq->isList());

        $seq = $this->seq(['a' => 1, 'b' => 2]);
        self::assertFalse($seq->isList());
    }

    public function test_join(): void
    {
        $seq = $this->seq([1, 2]);
        self::assertEquals('1, 2', $seq->join(', '));
        self::assertEquals('[1, 2', $seq->join(', ', '['));
        self::assertEquals('[1, 2]', $seq->join(', ', '[', ']'));

        $seq = $this->seq(['a' => 1, 'b' => 2]);
        self::assertEquals('1, 2', $seq->join(', '));
        self::assertEquals('[1, 2', $seq->join(', ', '['));
        self::assertEquals('[1, 2]', $seq->join(', ', '[', ']'));
    }

    public function test_jsonSerialize(): void
    {
        $seq = $this->seq([]);
        self::assertEquals([], $seq->jsonSerialize());

        $seq = $this->seq(['a' => 1, 'b' => 2]);
        self::assertEquals(['a' => 1, 'b' => 2], $seq->jsonSerialize());
    }

    public function test_keyBy(): void
    {
        $seq = $this->seq([1, 2])->keyBy(fn($v) => 'a'.$v);
        self::assertEquals(['a1' => 1, 'a2' => 2], $seq->toArray());

        $seq = $this->seq([['id' => 'b'], ['id' => 'c']])->keyBy(fn($v) => $v['id']);
        self::assertEquals(['b' => ['id' => 'b'], 'c' => ['id' => 'c']], $seq->toArray());
    }

    public function test_keyBy_with_duplicate_key(): void
    {
        $this->expectException(DuplicateKeyException::class);
        $this->seq([['id' => 'b'], ['id' => 'b']])->keyBy(fn($v) => $v['id']);
    }

    public function test_keyBy_with_overwritten_key(): void
    {
        $seq = $this->seq([['id' => 'b', 1], ['id' => 'b', 2]])->keyBy(fn($v) => $v['id'], true);
        self::assertEquals(['b' => ['id' => 'b', 2]], $seq->toArray());

        $this->expectException(DuplicateKeyException::class);
        $this->seq([['id' => 'b', 1], ['id' => 'b', 2]])->keyBy(fn($v) => $v['id']);
    }

    public function test_keyBy_with_invalid_key(): void
    {
        $this->expectException(InvalidKeyException::class);
        $this->seq([['id' => 'b', 1], ['id' => 'b', 2]])->keyBy(fn($v) => false);
    }

    public function test_keys(): void
    {
        $keys = $this->seq([1,2])->keys();
        self::assertEquals([0,1], $keys->toArray());

        $keys = $this->seq(['a' => 1, 'b' => 2])->keys();
        self::assertEquals(['a', 'b'], $keys->toArray());
    }

    public function test_last(): void
    {
        $seq = $this->seq([]);
        self::assertEquals(null, $seq->last());

        $seq = $this->seq([1, 2]);
        self::assertEquals(null, $seq->last(fn(int $i) => $i > 2));

        $seq = $this->seq([10, 20]);
        self::assertEquals(20, $seq->last());

        $seq = $this->seq([10, 20]);
        self::assertEquals(20, $seq->last());
        self::assertEquals(20, $seq->last(fn($v, $k) => $k === 1));
        self::assertEquals(20, $seq->last(fn($v, $k) => $v === 20));
    }

    public function test_lastIndex(): void
    {
        $seq = $this->seq([10, 20, 20]);
        self::assertEquals(1, $seq->lastIndex(fn($v, $k) => $k === 1));
        self::assertEquals(2, $seq->lastIndex(fn($v, $k) => $v === 20));
        self::assertEquals(null, $seq->lastIndex(fn() => false));
    }

    public function test_lastKey(): void
    {
        $seq = $this->seq(['a' => 10, 'b' => 20, 'c' => 20]);
        self::assertEquals('c', $seq->lastKey());
        self::assertEquals('b', $seq->lastKey(fn($v, $k) => $k === 'b'));
        self::assertEquals('c', $seq->lastKey(fn($v, $k) => $v === 20));
        self::assertEquals(null, $seq->lastKey(fn() => false));
    }

    public function test_lastOr(): void
    {
        $seq = $this->seq([10, 20]);
        self::assertEquals(20, $seq->lastOr(null));
        self::assertEquals(20, $seq->lastOr(null, fn($v, $k) => $k === 1));
        self::assertEquals(20, $seq->lastOr(null, fn($v, $k) => $v === 20));
        self::assertEquals(null, $seq->lastOr(null, fn() => false));
    }

    public function test_lastOrFail_empty(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Iterable must contain at least one element.');
        $this->seq([])->lastOrFail();
    }

    public function test_lastOrFail_bad_condition(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Failed to find matching condition.');
        $this->seq([1,2])->lastOrFail(fn(int $i) => $i > 2);
    }

    public function test_map(): void
    {
        $seq = $this->seq([1, 2, 3]);
        self::assertEquals([2, 4, 6], $seq->map(fn($i) => $i * 2)->toArray());
        self::assertEquals([0, 1, 2], $seq->map(fn($i, $k) => $k)->toArray());

        $seq = $this->seq(['a' => 1, 'b' => 2, 'c' => 3]);
        self::assertEquals(['a' => 2, 'b' => 4, 'c' => 6], $seq->map(fn($i) => $i * 2)->toArray());
    }

    public function test_max(): void
    {
        $seq = $this->seq([1, 2, 3, 10, 1]);
        self::assertEquals(10, $seq->max());

        $seq = $this->seq([100, 2, 3, 10, 1]);
        self::assertEquals(100, $seq->max());

        $seq = $this->seq([1, 2, 3, 10, 1, -100, 90]);
        self::assertEquals(90, $seq->max());
    }

    public function test_maxBy(): void
    {
        self::assertEquals(
            2,
            $this->seq(['a' => 2, 'b' => 1])->maxBy(fn($v, $k) => $v),
            'maxBy using value'
        );

        self::assertEquals(1,
            $this->seq(['a' => 2, 'b' => 1])->maxBy(fn($v, $k) => $k),
            'maxBy using key'
        );
    }

    public function test_maxBy_with_empty(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('$iterable must contain at least one value');
        $this->seq([])->maxBy(fn(array $arr) => 1);
    }

    public function test_merge(): void
    {
        $empty = $this->seq([]);
        $merged = $empty->merge([1, [2]]);
        self::assertNotSame($empty, $merged);
        self::assertCount(0, $empty);
        self::assertEquals([1, [2]], $merged->toArray());

        $empty = $this->seq([]);
        $merged = $empty->merge([1, [2]]);
        self::assertEquals([1, [2]], $merged->toArray());

        $empty = $this->seq(['0' => 1]);
        $merged = $empty->merge([1, [2]]);
        self::assertEquals(['0' => 1, 1, [2]], $merged->toArray());

        $assoc = $this->seq([1, 'a' => [1, 2]]);
        $merged = $assoc->merge([1, 'a' => [3]]);
        self::assertSame([1, 'a' => [3], 1], $merged->toArray());

        $assoc = $this->seq([1, 'a' => [1, 2], 2]);
        $merged = $assoc->merge(['a' => [3], 3]);
        self::assertSame([1, 'a' => [3], 2, 3], $merged->toArray());
    }

    public function test_mergeRecursive(): void
    {
        $seq = $this->seq([])->mergeRecursive([]);
        self::assertEquals([], $seq->toArray());

        $seq = $this->seq([1, 2])->mergeRecursive([3]);
        self::assertEquals([1, 2, 3], $seq->toArray());

        $seq = $this->seq(['a' => 1])->mergeRecursive(['a' => 2]);
        self::assertEquals(['a' => 2], $seq->toArray());

        $seq = $this->seq(['a' => 1])->mergeRecursive(['b' => 2, 'a' => 2]);
        self::assertEquals(['a' => 2, 'b' => 2], $seq->toArray());

        $seq = $this->seq(['a' => 1])->mergeRecursive(['b' => 2]);
        self::assertEquals(['a' => 1, 'b' => 2], $seq->toArray());

        $seq = $this->seq(['a' => 1])->mergeRecursive(['a' => ['c' => 1]]);
        self::assertEquals(['a' => ['c' => 1]], $seq->toArray());

        $seq = $this->seq(['a' => [1,2]])->mergeRecursive(['a' => ['c' => 1]]);
        self::assertEquals(['a' => [1, 2, 'c' => 1]], $seq->toArray());

        $seq = $this->seq(['a' => ['b' => 1], 'd' => 4])->mergeRecursive(['a' => ['c' => 2], 'b' => 3]);
        self::assertEquals(['a' => ['b' => 1, 'c' => 2], 'b' => 3, 'd' => 4], $seq->toArray());
    }

    public function test_min(): void
    {
        $seq = $this->seq([1, 2, 3, 10, -1]);
        self::assertEquals(-1, $seq->min());

        $seq = $this->seq([0, -1]);
        self::assertEquals(-1, $seq->min());

        $seq = $this->seq([1, 10, -100]);
        self::assertEquals(-100, $seq->min());
    }

    public function test_minBy(): void
    {
        self::assertEquals(
            null,
            $this->seq([])->minBy(fn(array $arr) => 1),
            'minBy with empty array'
        );

        self::assertEquals(
            1,
            $this->seq(['a' => 2, 'b' => 1])->minBy(fn($v, $k) => $v),
            'minBy using value'
        );

        self::assertEquals(
            2,
            $this->seq(['a' => 2, 'b' => 1])->minBy(fn($v, $k) => $k),
            'minBy using key'
        );
    }

    public function test_minMax(): void
    {
        $seq = $this->seq([1]);
        self::assertEquals(['min' => 1, 'max' => 1], $seq->minMax());

        $seq = $this->seq([1, 10, -100]);
        self::assertEquals(['min' => -100, 'max' => 10], $seq->minMax());
    }

    public function test_minMax_empty(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Iterable must contain at least one element.');
        $this->seq([])->minMax();
    }

    public function test_notContains(): void
    {
        self::assertTrue($this->seq([])->notContains(0));
        self::assertTrue($this->seq([])->notContains(null));
        self::assertTrue($this->seq([])->notContains([]));
        self::assertTrue($this->seq([null, 0])->notContains(false));
        self::assertTrue($this->seq([null, 0])->notContains(1));
        self::assertTrue($this->seq(['a' => 1])->notContains('a'));
        self::assertFalse($this->seq([null, 0])->notContains(null));
        self::assertFalse($this->seq([null, []])->notContains([]));
        self::assertFalse($this->seq(['a' => 1, 0])->notContains(1));
    }

    public function test_notContainsKey(): void
    {
        self::assertTrue($this->seq([])->notContainsKey(0));
        self::assertTrue($this->seq([])->notContainsKey(1));
        self::assertTrue($this->seq(['b' => 1])->notContainsKey('a'));
        self::assertFalse($this->seq([1])->notContainsKey(0));
        self::assertFalse($this->seq([11 => 1])->notContainsKey(11));
        self::assertFalse($this->seq(['a' => 1, 0])->notContainsKey('a'));
    }

    public function test_notEquals(): void
    {
        self::assertTrue($this->seq([])->notEquals($this->seq([1])));
        self::assertTrue($this->seq([])->notEquals($this->seq([null])));
        self::assertTrue($this->seq(['b' => 1])->notEquals($this->seq(['a' => 1])));
        self::assertFalse($this->seq([1])->notEquals($this->seq([1])));
        self::assertFalse($this->seq(['a' => 1])->notEquals($this->seq(['a' => 1])));
    }
    public function test_only(): void
    {
        // with list array
        $seq = $this->seq([1, 2, 3]);
        self::assertEquals([2], $seq->only([1])->toArray());

        // with assoc array
        $seq = $this->seq(['a' => 1, 'b' => 2, 'c' => 3]);
        self::assertEquals(['a' => 1, 'b' => 2], $seq->only(['a', 'b'])->toArray());

        // different order of keys
        self::assertEquals(['c' => 3, 'b' => 2], $seq->only(['c', 'b'])->toArray());

        // different order of keys
        self::assertEquals(['c' => 3, 'b' => 2], $seq->only(['x' => 'c', 'b'])->toArray());
    }

    public function test_only_WithUndefinedKey(): void
    {
        $this->expectException(ErrorException::class);
        $this->expectExceptionMessage('Undefined array key "a"');
        self::assertEquals([], $this->seq([])->only(['a'])->toArray());
    }

    public function test_prioritize(): void
    {
        $seq = $this->seq([1, 2, 3])->prioritize(fn(int $i) => $i === 2);
        self::assertEquals([2, 1, 3], $seq->values()->toArray());

        $seq = $this->seq(['a' => 1, 'bc' => 2, 'de' => 2, 'b' => 2])->prioritize(fn($_, string $k) => strlen($k) > 1);
        self::assertEquals(['bc', 'de', 'a', 'b'], $seq->keys()->toArray());

        $seq = $this->seq([1, 2, 3])->prioritize(fn() => false);
        self::assertEquals([1, 2, 3], $seq->toArray());
    }

    public function test_reduce(): void
    {
        $reduced = $this->seq(['a' => 1])->reduce(fn(int $c, $i, $k) => 0);
        self::assertEquals(1, $reduced);

        $reduced = $this->seq(['a' => 1, 'b' => 2])->reduce(fn($val, $i) => $i * 2);
        self::assertEquals(4, $reduced);

        $reduced = $this->seq([1, 2, 3])->reduce(fn(int $c, $i, $k) => $c + $i);
        self::assertEquals(6, $reduced);
    }

    public function test_reduce_unable_to_guess_initial(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Expected a value other than null.');
        $this->seq([])->reduce(fn($c, $i, $k) => $k);
    }

    public function test_repeat(): void
    {
        $seq = $this->seq([1])->repeat(3);
        self::assertEquals([1, 1, 1], $seq->toArray(), 'Repeat single 3 times');

        $seq = $this->seq([1, 2])->repeat(2);
        self::assertEquals([1, 2, 1, 2], $seq->toArray(), 'Repeat multiple 3 times');

        $seq = $this->seq(['a' => 1, 'b' => 2])->repeat(2);
        self::assertEquals([1, 2, 1, 2], $seq->toArray(), 'Repeat hash 3 times (loses the keys)');

        $seq = $this->seq([1])->repeat(0);
        self::assertEquals([], $seq->toArray(), 'Repeat 0 times (does nothing)');
    }

    public function test_repeat_negative_times(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Expected a value greater than or equal to 0. Got: -1');

        $seq = $this->seq([1])->repeat(-1);
        self::assertEquals([], $seq->toArray(), 'Repeat -1 times (throws error)');
    }

    public function test_reverse(): void
    {
        $seq = $this->seq([])->reverse();
        self::assertEquals([], $seq->toArray());

        $seq = $this->seq([1, 2])->reverse();
        self::assertEquals([2, 1], $seq->toArray());

        $seq = $this->seq([100 => 1, 200 => 2])->reverse();
        self::assertEquals([200 => 2, 100 => 1], $seq->toArray());

        $seq = $this->seq(['a' => 1, 'b' => 2, 3])->reverse();
        self::assertEquals([3, 'b' => 2, 'a' => 1], $seq->toArray());

        $seq = $this->seq(['a' => 1, 2, 3, 4])->reverse();
        self::assertEquals([2 => 4, 1 => 3, 0 => 2, 'a' => 1], $seq->toArray());
    }

    public function test_rotate(): void
    {
        $seq = $this->seq(['a' => 1, 'b' => 2, 'c' => 3])->rotate(1);
        self::assertEquals(['b', 'c', 'a'], $seq->keys()->toArray());

        $seq = $this->seq(['a' => 1, 'b' => 2, 'c' => 3])->rotate(2);
        self::assertEquals(['c' => 3, 'a' => 1, 'b' => 2], $seq->toArray());

        $seq = $this->seq(['a' => 1, 'b' => 2, 'c' => 3])->rotate(-1);
        self::assertEquals(['c', 'a', 'b'], $seq->keys()->toArray());

        $seq = $this->seq(['a' => 1, 'b' => 2, 'c' => 3])->rotate(-2);
        self::assertEquals(['b', 'c', 'a'], $seq->keys()->toArray());
    }

    public function test_sample(): void
    {
        mt_srand(100);
        self::assertEquals(8, $this->seq(range(0, 10))->sample());
    }

    public function test_sample_Empty(): void
    {
        $this->expectException(ValueError::class);
        $this->expectExceptionMessage('array_rand(): Argument #1 ($array) cannot be empty');
        $this->seq([])->sample();
    }

    public function test_sampleMany(): void
    {
        mt_srand(100);
        self::assertEquals([8, 9], $this->seq(range(0, 10))->sampleMany(2)->toArray());
    }

    public function test_satisfyAll(): void
    {
        $seq = $this->seq([]);
        self::assertTrue($seq->satisfyAll(static fn($v) => is_int($v)));

        $seq = $this->seq([1, 2, 3]);
        self::assertTrue($seq->satisfyAll(static fn($v) => is_int($v)));

        $seq = $this->seq(['a' => 1, 'b' => 2, 'c' => 3]);
        self::assertTrue($seq->satisfyAll(static fn($v, $k) => is_string($k)));

        $seq = $this->seq(['a' => 1, 'b' => 2, 'c' => 3, 4, '1']);
        self::assertFalse($seq->satisfyAll(static fn($k) => is_string($k)));
    }

    public function test_satisfyAny(): void
    {
        $empty = $this->seq([]);
        self::assertFalse($empty->satisfyAny(static fn() => true));

        $seq = $this->seq([1, null, 2, [3], false]);
        self::assertTrue($seq->satisfyAny(static fn($v) => true));
        self::assertFalse($seq->satisfyAny(static fn($v) => false));
        self::assertTrue($seq->satisfyAny(static fn($v) => is_array($v)));

        $seq = $this->seq(['a' => 1, 'b' => 2]);
        self::assertTrue($seq->satisfyAny(static fn($v, $k) => true));
        self::assertFalse($seq->satisfyAny(static fn($v) => false));
        self::assertTrue($seq->satisfyAny(static fn($v, $k) => $k === 'b'));
    }

    public function test_shuffle(): void
    {
        mt_srand(100);
        self::assertEquals([1, 2, 4, 3, 2], $this->seq([1, 2, 2, 3, 4])->shuffle()->toArray());
        self::assertSame(['a' => 1, 'c' => 3, 'b' => 2, 'd' => 4], $this->seq(['a' => 1, 'b' => 2, 'c' => 3, 'd' => 4])->shuffle()->toArray());
    }

    public function test_slice(): void
    {
        $collect = $this->seq([1, 2, 3])->slice(1);
        self::assertEquals([2, 3], $collect->toArray());

        $collect = $this->seq([1, 2, 3])->slice(0, -1);
        self::assertEquals([1, 2], $collect->toArray());
    }

    public function test_sole(): void
    {
        self::assertEquals(1, $this->seq([1])->sole());
        self::assertEquals(1, $this->seq(['a' => 1])->sole());
        self::assertEquals(2, $this->seq([1, 2, 3])->sole(fn(int $i) => $i === 2));
    }

    public function test_sole_zero_item(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Expected only one element in result. 0 given.');
        $this->seq([])->sole();
    }

    public function test_sole_more_than_one_item(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Expected only one element in result. 2 given.');
        $this->seq([1, 2])->sole();
    }

    public function test_sort(): void
    {
        $seq = $this->seq([4, 2, 1, 3])->sort();
        self::assertEquals([1, 2, 3, 4], $seq->toArray());

        $seq = $this->seq(['30', '2', '100'])->sort(SORT_NATURAL);
        self::assertEquals(['2', '30', '100'], $seq->toArray());

        $seq = $this->seq(['a' => 3, 'b' => 1, 'c' => 2])->sort();
        self::assertEquals(['b' => 1, 'c' => 2, 'a' => 3], $seq->toArray());
    }

    public function test_sortBy(): void
    {
        $seq = $this->seq([4, 2, 1, 3])->sortBy(fn($v) => $v);
        self::assertEquals([1, 2, 3, 4], $seq->toArray());

        $seq = $this->seq(['b' => 0, 'a' => 1, 'c' => 2])->sortBy(fn($v, $k) => $k);
        self::assertEquals(['a' => 1, 'b' => 0, 'c' => 2], $seq->toArray());
    }

    public function test_sortByDesc(): void
    {
        $seq = $this->seq([4, 2, 1, 3])->sortByDesc(fn($v) => $v);
        self::assertEquals([4, 3, 2, 1], $seq->toArray());

        $seq = $this->seq(['b' => 0, 'a' => 1, 'c' => 2])->sortBy(fn($v, $k) => $k);
        self::assertEquals(['c' => 2, 'b' => 0, 'a' => 1], $seq->toArray());
    }

    public function test_sortDesc(): void
    {
        $seq = $this->seq([4, 2, 1, 3])->sortDesc();
        self::assertEquals([4, 3, 2, 1], $seq->toArray());

        $seq = $this->seq(['30', '100', '2'])->sortDesc(SORT_NATURAL);
        self::assertEquals(['100', '30', '2'], $seq->toArray());

        $seq = $this->seq(['a' => 3, 'b' => 1, 'c' => 2])->sortDesc();
        self::assertEquals(['a' => 3, 'c' => 2, 'b' => 1], $seq->toArray());
    }

    public function test_sortKeys(): void
    {
        $seq = $this->seq(['b' => 0, 'a' => 1, 'c' => 2])->sortByKey();
        self::assertEquals(['a' => 1, 'b' => 0, 'c' => 2], $seq->toArray());

        $seq = $this->seq(['2' => 0, '100' => 1, '30' => 2])->sortByKey(SORT_NATURAL);
        self::assertEquals(['2' => 0, '30' => 2, '100' => 1], $seq->toArray());
    }

    public function test_sortKeysDesc(): void
    {
        $seq = $this->seq(['b' => 0, 'a' => 1, 'c' => 2])->sortByKeyDesc();
        self::assertEquals(['c' => 2, 'b' => 0, 'a' => 1], $seq->toArray());

        $seq = $this->seq(['2' => 0, '100' => 1, '30' => 2])->sortByKeyDesc(SORT_NATURAL);
        self::assertEquals(['100' => 1, '30' => 2, '2' => 0], $seq->toArray());
    }

    public function test_sortWith(): void
    {
        $seq = $this->seq([1, 3, 2])->sortWith(static fn($a, $b) => ($a === $b ? 0 : (($a < $b) ? -1 : 1)));
        self::assertEquals([1, 2, 3], $seq->toArray());

        $seq = $this->seq(['b' => 1, 'a' => 3, 'c' => 2])->sortWith(static fn($a, $b) => ($a === $b ? 0 : (($a < $b) ? -1 : 1)));
        self::assertEquals(['b' => 1, 'c' => 2, 'a' => 3], $seq->toArray());
    }

    public function test_sortWithKey(): void
    {
        $seq = $this->seq([1 => 'a', 3 => 'b', 2 => 'c'])->sortWithKey(static fn($a, $b) => ($a === $b ? 0 : (($a < $b) ? -1 : 1)));
        self::assertEquals([1 => 'a', 2 => 'c', 3 => 'b'], $seq->toArray());
    }

    public function test_sum(): void
    {
        $sum = $this->seq(['b' => 1, 'a' => 3, 'c' => 2])->sum();
        self::assertEquals(6, $sum);

        $sum = $this->seq([1, 1, 1])->sum();
        self::assertEquals(3, $sum);

        $sum = $this->seq([0.1, 0.2])->sum();
        self::assertEquals(0.3, $sum);

        $sum = $this->seq([])->sum();
        self::assertEquals(0, $sum);
    }

    public function test_sum_throw_on_sum_of_string(): void
    {
        $this->expectException(TypeError::class);
        $this->expectExceptionMessage('Unsupported operand types: int + string');
        $this->seq(['a', 'b'])->sum();
    }

    public function test_symDiff(): void
    {
    // empty array1
    self::assertEquals([1], Arr::symDiff([], [1]));

    // empty array2
    self::assertEquals([1], Arr::symDiff([1], []));

    // same but not same order
    self::assertEquals([], Arr::symDiff([1, 2], [2, 1]));

    // list
    self::assertEquals([1, 3], Arr::symDiff([1, 2], [2, 3]));

    // assoc
    self::assertEquals(['a' => 1, 'd' => 3], Arr::symDiff(['a' => 1, 'b' => 2], ['c' => 2, 'd' => 3]));

    // assoc but with same key (overwritten by array2)
    self::assertEquals(['a' => 3], Arr::symDiff(['a' => 1, 'b' => 2], ['c' => 2, 'a' => 3]));

    // array1 is list and array2 is assoc => retain assoc
    $result = Arr::symDiff([2, 1], ['a' => 2, 'b' => 3]);
    self::assertEquals([1 => 1, 'b' => 3], $result);
    self::assertEquals([1, 3], Arr::values($result));

    // array1 is assoc and array2 is list => retain assoc
    $result = Arr::symDiff(['a' => 2, 'b' => 3], [2, 1]);
    self::assertEquals(['b' => 3, 1 => 1], $result);
    self::assertEquals([3, 1], Arr::values($result));
    }

    public function test_take(): void
    {
        $seq = $this->seq([2, 3, 4])->take(2);
        self::assertEquals([2, 3], $seq->toArray());

        $seq = $this->seq([2, 3, 4])->take(0);
        self::assertEquals([], $seq->toArray());

        $seq = $this->seq(['b' => 1, 'a' => 3, 'c' => 2])->take(1);
        self::assertEquals(['b' => 1], $seq->toArray());
    }

    public function test_take_fail_on_negative_amount(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Expected a value greater than or equal to 0. Got: -1');
        $this->seq(['a' => 1])->take(-1);
    }

    public function test_takeUntil(): void
    {
        $seq = $this->seq([1, 1, 3, 2])->takeUntil(fn($v) => $v > 2);
        self::assertEquals([1, 1], $seq->toArray());

        $seq = $this->seq(['b' => 1, 'a' => 3, 'c' => 2])->takeUntil(fn($v) => $v > 2);
        self::assertEquals(['b' => 1], $seq->toArray());

        $seq = $this->seq(['b' => 1, 'a' => 3, 'c' => 2])->takeUntil(fn($v) => false);
        self::assertEquals(['b' => 1, 'a' => 3, 'c' => 2], $seq->toArray());

        $seq = $this->seq(['b' => 1, 'a' => 3, 'c' => 2])->takeUntil(fn($v) => true);
        self::assertEquals([], $seq->toArray());
    }

    public function test_takeWhile(): void
    {
        $seq = $this->seq([1, 1, 3, 2])->takeWhile(fn($v) => $v <= 2);
        self::assertEquals([1, 1], $seq->toArray());

        $seq = $this->seq(['b' => 1, 'a' => 3, 'c' => 4])->takeWhile(fn($v) => $v < 4);
        self::assertEquals(['b' => 1, 'a' => 3], $seq->toArray());

        $seq = $this->seq(['b' => 1, 'a' => 3, 'c' => 2])->takeWhile(fn($v) => false);
        self::assertEquals([], $seq->toArray());

        $seq = $this->seq(['b' => 1, 'a' => 3, 'c' => 2])->takeWhile(fn($v) => true);
        self::assertEquals(['b' => 1, 'a' => 3, 'c' => 2], $seq->toArray());
    }

    public function test_tap(): void
    {
        $seq = $this->seq([1, 2])->tap(fn() => 100);
        self::assertEquals([1, 2], $seq->toArray());

        $cnt = 0;
        $seq = $this->seq([])->tap(function() use (&$cnt) { ++$cnt; });
        self::assertEquals([], $seq->toArray());
        self::assertEquals(1, $cnt);
    }

    public function test_toArray(): void
    {
        self::assertEquals([], $this->seq([])->toArray());
        self::assertEquals([1, 2], $this->seq([1, 2])->toArray());
        self::assertEquals(['a' => 1], $this->seq(['a' => 1])->toArray());

        $inner = $this->seq([1, 2]);
        self::assertEquals(['a' => $inner], $this->seq(['a' => $inner])->toArray());
    }

    public function test_toArrayRecursive(): void
    {
        // no depth defined
        $inner = $this->seq([1, 2]);
        $array = $this->seq(['a' => $inner])->toArrayRecursive();
        self::assertEquals(['a' => [1, 2]], $array);

        // test each depth
        $inner1 = $this->seq([1]);
        $inner2 = $this->seq([2, 3, $inner1]);
        $seq = $this->seq(['a' => $inner2]);
        self::assertEquals(['a' => $inner2], $seq->toArrayRecursive(1));
        self::assertEquals(['a' => [2, 3, $inner1]], $seq->toArrayRecursive(2));
        self::assertEquals(['a' => [2, 3, [1]]], $seq->toArrayRecursive(3));
    }

    public function test_toJson(): void
    {
        $json = $this->seq([1, 2])->toJson();
        self::assertEquals("[1,2]", $json);

        $json = $this->seq(['a' => 1, 'b' => 2])->toJson();
        self::assertEquals("{\"a\":1,\"b\":2}", $json);

        $json = $this->seq(["あ"])->toJson();
        self::assertEquals("[\"あ\"]", $json);

        $json = $this->seq([1])->toJson(JSON_PRETTY_PRINT);
        self::assertEquals("[\n    1\n]", $json);
    }

    public function test_toUrlQuery(): void
    {
        $query = $this->seq(['a' => 1])->toUrlQuery('t');
        self::assertEquals(urlencode('t[a]').'=1', $query);

        $query = $this->seq(['a' => 1, 'b' => 2])->toUrlQuery();
        self::assertEquals("a=1&b=2", $query);
    }

    public function test_union(): void
    {
        $seq = $this->seq([])->union([]);
        self::assertEquals([], $seq->toArray());

        $seq = $this->seq(['a' => 1])->union(['a' => 2]);
        self::assertEquals(['a' => 1], $seq->toArray());

        $seq = $this->seq(['a' => ['b' => 1]])->union(['a' => ['c' => 2]]);
        self::assertEquals(['a' => ['b' => 1]], $seq->toArray());
    }

    public function test_unionRecursive(): void
    {
        $seq = $this->seq([])->unionRecursive([]);
        self::assertEquals([], $seq->toArray());

        $seq = $this->seq([1, 2])->unionRecursive([3]);
        self::assertEquals([1, 2, 3], $seq->toArray());

        $seq = $this->seq(['a' => 1])->unionRecursive(['a' => 2]);
        self::assertEquals(['a' => 1], $seq->toArray());

        $seq = $this->seq(['a' => 1])->unionRecursive(['b' => 2, 'a' => 2]);
        self::assertEquals(['a' => 1, 'b' => 2], $seq->toArray());

        $seq = $this->seq(['a' => 1])->unionRecursive(['b' => 2]);
        self::assertEquals(['a' => 1, 'b' => 2], $seq->toArray());

        $seq = $this->seq(['a' => 1])->unionRecursive(['a' => ['c' => 1]]);
        self::assertEquals(['a' => 1], $seq->toArray());

        $seq = $this->seq(['a' => [1,2]])->unionRecursive(['a' => ['c' => 1]]);
        self::assertEquals(['a' => [1, 2, 'c' => 1]], $seq->toArray());

        $seq = $this->seq(['a' => ['b' => 1], 'd' => 4])->unionRecursive(['a' => ['c' => 2], 'b' => 3]);
        self::assertEquals(['a' => ['b' => 1, 'c' => 2], 'b' => 3, 'd' => 4], $seq->toArray());
    }

    public function test_unique(): void
    {
        $seq = $this->seq([])->unique();
        self::assertEquals([], $seq->toArray());

        $seq = $this->seq([1, 1, 2, 2])->unique();
        self::assertEquals([1, 2], $seq->toArray());

        $seq = $this->seq(['a' => 1, 'b' => 2, 'c' => 2])->unique();
        self::assertEquals(['a' => 1, 'b' => 2], $seq->toArray());

        $values = ['3', 3, null, '', 0, true, false];
        $seq = $this->seq([])->merge($values)->merge($values)->unique();
        self::assertEquals($values, $seq->toArray());

        $seq = $this->seq($values)->repeat(2)->unique();
        self::assertEquals($values, $seq->toArray());
    }

    public function test_uniqueBy(): void
    {
        $seq = $this->seq([])->uniqueBy(static fn() => 1);
        self::assertEquals([], $seq->toArray());

        $seq = $this->seq([1,2,3,4])->uniqueBy(static fn($v) => $v % 2);
        self::assertEquals([1, 2], $seq->toArray());

        $seq = $this->seq(['a' => 1, 'b' => 2, 'c' => 2])->uniqueBy(static fn($v) => $v % 2);
        self::assertEquals(['a' => 1, 'b' => 2], $seq->toArray());

        $values = ['3', 3, null, '', 0, true, false];
        $seq = $this->seq($values)->repeat(2)->uniqueBy(static fn($v) => $v);
        self::assertEquals($values, $seq->toArray());
    }

    public function test_values(): void
    {
        $seq = $this->seq([])->values();
        self::assertEquals([], $seq->toArray());

        $seq = $this->seq([1, 1, 2])->values()->reverse();
        self::assertEquals([2, 1, 1], $seq->toArray());

        $seq = $this->seq(['a' => 1, 'b' => 2])->values();
        self::assertEquals([1, 2], $seq->toArray());
    }
    public function test_of(): void
    {
        self::assertEquals([], Arr::of());

        self::assertEquals([1, 2, 3], Arr::of(1, 2, 3));

        self::assertEquals([1, 'a' => 2], Arr::of(1, a: 2));

        self::assertEquals(['a' => 1, 'b' => 2], Arr::of(a: 1, b: 2));
    }
     **/
}
