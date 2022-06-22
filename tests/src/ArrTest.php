<?php declare(strict_types=1);

namespace Tests\Kirameki\Utils;

use DivisionByZeroError;
use Exception;
use InvalidArgumentException;
use Kirameki\Utils\Arr;
use Kirameki\Utils\Exception\DuplicateKeyException;
use Kirameki\Utils\Iter;
use Kirameki\Utils\Str;
use Kirameki\Utils\Support\Nil;
use LogicException;
use RuntimeException;
use stdClass;
use TypeError;
use function array_keys;
use function array_values;
use function implode;
use function in_array;
use function mt_srand;
use function range;
use function strlen;
use function substr;
use function urlencode;

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
        $miss = Nil::instance();
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

        // empty with condition
        self::assertEquals(0, Arr::count([], static fn() => true));

        // condition success
        self::assertEquals(2, Arr::count([1, 2], static fn() => true));

        // condition fail
        self::assertEquals(0, Arr::count([1, 2], static fn() => false));

        // condition partially success
        self::assertEquals(2, Arr::count([1, 2, 3], static fn($v) => $v > 1));

        // condition checked with key
        self::assertEquals(1, Arr::count(['a' => 1, 'b' => 2], static fn($v,$k) => $k === 'a'));
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

        // with custom diff subject
        $diff = Arr::diff([[1], [2]], [[2], [3]], by: static fn(array $a, array $b) => $a[0] <=> $b[0]);
        self::assertEquals([[1]], $diff);
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

        // with custom diff subject
        $by = static fn(string $a, string $b) => substr($a, 1) <=> substr($b, 1);
        $diff = Arr::diffKeys(['a1' => 0, 'b2' => 1], ['c1' => 2], $by);
        self::assertEquals(['b2' => 1], $diff);
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

    public function test_get(): void
    {
        $list = [1, 2];
        self::assertEquals(2, Arr::get($list, 1));
        self::assertEquals(null, Arr::get($list, 2));

        $assoc = ['a' => 1, 'b' => 2];
        self::assertEquals(1, Arr::get($assoc, 'a'));
        self::assertEquals(null, Arr::get($assoc, 'f'));
    }

    public function test_getOr(): void
    {
        $assoc = ['a' => 1, 'b' => 2];
        self::assertEquals(1, Arr::getOr($assoc, 'a', Nil::instance()));
        self::assertEquals(Nil::instance(), Arr::getOr($assoc, 'c', Nil::instance()));
    }

    public function test_getOrFail(): void
    {
        $list = [1, 2];
        self::assertEquals(2, Arr::getOrFail($list, 1));

        $assoc = ['a' => 1, 'b' => 2];
        self::assertEquals(1, Arr::getOrFail($assoc, 'a'));
    }

    public function test_getOrFail_invalid_key_exception(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Undefined array key 2');
        Arr::getOrFail([1, 2], 2);
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
        self::assertEquals(null, Arr::first([1,2], static fn(int $i) => $i > 2));

        // one element
        self::assertEquals(1, Arr::first([1], static fn($v) => true));

        // list
        self::assertEquals(10, Arr::first([10, 20]));
        self::assertEquals(20, Arr::first([10, 20], static fn($v, $k) => $k === 1));
        self::assertEquals(20, Arr::first([10, 20], static fn($v, $k) => $v === 20));

        // assoc
        self::assertEquals(10, Arr::first(['a' => 10, 'b' => 20, 'c' => 30]));
        self::assertEquals(10, Arr::first(['a' => 10, 'b' => 20, 'c' => 30], static fn($v, $k) => $k === 'a'));
        self::assertEquals(20, Arr::first(['a' => 10, 'b' => 20, 'c' => 30], static fn($v, $k) => $k === 'b'));
    }

    public function test_firstIndex(): void
    {
        // empty
        self::assertEquals(null, Arr::firstIndex([], static fn($v, $k) => true));

        // list
        self::assertEquals(2, Arr::firstIndex([10, 20, 20, 30], static fn($v, $k) => $k === 2));
        self::assertEquals(1, Arr::firstIndex([10, 20, 20, 30], static fn($v, $k) => $v === 20));
        self::assertEquals(null, Arr::firstIndex([10, 20, 20, 30], static fn() => false));

        // assoc
        self::assertEquals(1, Arr::firstIndex(['a' => 1, 'b' => 2, 'c' => 3], static fn($v, $k) => $v === 2));
        self::assertEquals(2, Arr::firstIndex(['a' => 1, 'b' => 2, 'c' => 3], static fn($v, $k) => $k === 'c'));
        self::assertEquals(1, Arr::firstIndex(['a' => 1, 'b' => 2, 'c' => 3], static fn($v, $k) => $v > 1));
        self::assertEquals(null, Arr::firstIndex(['a' => 1, 'b' => 2, 'c' => 3], static fn($v, $k) => $v > 10));
    }

    public function test_firstKey(): void
    {
        // empty
        self::assertEquals(null, Arr::firstKey([], static fn($v, $k) => true));

        // list
        self::assertEquals(null, Arr::firstKey([10, 20, 20, 30], static fn() => false));
        self::assertEquals(1, Arr::firstKey([10, 20, 30], static fn($v, $k) => $v === 20));
        self::assertEquals(2, Arr::firstKey([10, 20, 30], static fn($v, $k) => $k === 2));

        // assoc
        self::assertEquals(null, Arr::firstKey(['a' => 1, 'b' => 2, 'c' => 3], static fn($v, $k) => $v > 10));
        self::assertEquals('b', Arr::firstKey(['a' => 1, 'b' => 2, 'c' => 3], static fn($v, $k) => $v === 2));
        self::assertEquals('c', Arr::firstKey(['a' => 1, 'b' => 2, 'c' => 3], static fn($v, $k) => $k === 'c'));
    }

    public function test_firstOr(): void
    {
        // empty
        self::assertEquals(INF, Arr::firstOr([], INF));

        // list
        self::assertEquals(1, Arr::firstOr([1, 2], INF));
        self::assertEquals(2, Arr::firstOr([1, 2], INF, static fn($v, $k) => $k === 1));
        self::assertEquals(2, Arr::firstOr([1, 2], INF, static fn($v, $k) => $v === 2));
        self::assertEquals(INF, Arr::firstOr([1, 2], INF, static fn() => false));

        // assoc
        self::assertEquals(1, Arr::firstOr(['a' => 1, 'b' => 2], INF));
        self::assertEquals(2, Arr::firstOr(['a' => 1, 'b' => 2], INF, static fn($v, $k) => $k === 'b'));
        self::assertEquals(2, Arr::firstOr(['a' => 1, 'b' => 2], INF, static fn($v, $k) => $v === 2));
        self::assertEquals(INF, Arr::firstOr(['a' => 1, 'b' => 2], INF, static fn() => false));
    }

    public function test_firstOrFail(): void
    {
        // list
        self::assertEquals(1, Arr::firstOrFail([1, 2]));
        self::assertEquals(2, Arr::firstOrFail([1, 2], static fn($v, $k) => $k === 1));
        self::assertEquals(2, Arr::firstOrFail([1, 2], static fn($v, $k) => $v === 2));

        // assoc
        self::assertEquals(1, Arr::firstOrFail(['a' => 1, 'b' => 2]));
        self::assertEquals(2, Arr::firstOrFail(['a' => 1, 'b' => 2], static fn($v, $k) => $k === 'b'));
        self::assertEquals(2, Arr::firstOrFail(['a' => 1, 'b' => 2], static fn($v, $k) => $v === 2));
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
        Arr::firstOrFail([1,2], static fn(int $i) => $i > 2);
    }

    public function test_flatMap(): void
    {
        // empty
        self::assertEquals([], Arr::flatMap([], static fn($i) => $i));

        // return modified array
        self::assertEquals([1, -1, 2, -2], Arr::flatMap([1, 2], static fn($i) => [$i, -$i]));

        // simple flat
        self::assertEquals(['a', 'b'], Arr::flatMap([['a'], ['b']], static fn($a) => $a));

        // keys are lost since it cannot be retained
        self::assertEquals([1, 2, 2], Arr::flatMap([['a' => 1], [2], 2], static fn($a) => $a));
    }

    public function test_flatten(): void
    {
        // empty
        self::assertEquals([], Arr::flatten([]));

        // nothing to flatten
        self::assertEquals([1, 2], Arr::flatten([1, 2]));

        // flatten only 1 (default)
        self::assertEquals([1, [2, 2], 3], Arr::flatten([[1, [2, 2]], 3]));

        // flatten depth at 2
        self::assertEquals([1, 1, 2, [3, 3], 2, 1], Arr::flatten([[1], [1, [2, [3, 3], 2], 1]], 2));

        // assoc info is lost
        self::assertEquals([1, 2, 3], Arr::flatten(['a' => 1, 'b' => ['b1' => 2, 'b2' => 3]]));

        // assoc info is lost variant
        self::assertEquals(['a', 'b', 'd'], Arr::flatten([['a'], 'b', ['c' => 'd']]));
    }

    public function test_flatten_zero_depth(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Expected a positive integer. Got: 0');
        self::assertEquals([1, 2], Arr::flatten([1, 2], 0));
    }

    public function test_flatten_negative_depth(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Expected a positive integer. Got: -1');
        self::assertEquals([1, 2], Arr::flatten([1, 2], -1));
    }

    public function test_flip(): void
    {
        self::assertEquals([1, 2], array_keys(Arr::flip([1, 2])));
        self::assertEquals([0, 1], array_values(Arr::flip([1, 2])));

        self::assertEquals(['b' => 'a', 'd' => 'c'], Arr::flip(['a' => 'b', 'c' => 'd']));
    }

    public function test_flip_invalid_key_type(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Expected string or integer. Got: boolean');
        Arr::flip([true, false]);
    }

    public function test_fold(): void
    {
        $reduced = Arr::fold([], 0, static fn(int $i) => $i + 1);
        self::assertEquals(0, $reduced);

        $reduced = Arr::fold(['a' => 1, 'b' => 2], new stdClass(), static function(stdClass $c, int $i, string $k): stdClass {
            $c->$k = $i * 2;
            return $c;
        });
        self::assertEquals(['a' => 2, 'b' => 4], (array) $reduced);

        $reduced = Arr::fold([1, 2, 3], 0, static fn(int $c, $i, $k): int => $c + $i);
        self::assertEquals(6, $reduced);
    }

    public function test_groupBy(): void
    {
        // empty
        $grouped = Arr::groupBy([], static fn(int $n): int => $n % 3);
        self::assertEquals([], $grouped);

        // Closure
        $grouped = Arr::groupBy([1, 2, 3, 4, 5, 6], static fn(int $n): int => $n % 3);
        self::assertEquals([[3, 6], [1, 4], [2, 5]], $grouped);

        // key
        self::assertEquals([
            1 => [['id' => 1], ['id' => 1]],
            2 => [['id' => 2]]
        ],
            Arr::groupBy([ /** @phpstan-ignore-line */
                ['id' => 1], ['id' => 2], ['id' => 1],
            ], 'id'));

        // reindex: true
        $grouped = Arr::groupBy(['a' => 1, 'b' => 2, 'c' => 3, 'd' => 4], static fn(int $n): int => $n % 2, reindex: true);
        self::assertEquals([[2, 4], [1, 3]], $grouped);

        // reindex: false
        $grouped = Arr::groupBy(['a' => 1, 'b' => 2, 'c' => 3, 'd' => 4], static fn(int $n): int => $n % 2, reindex: false);
        self::assertEquals([['b' => 2, 'd' => 4], ['a' => 1, 'c' => 3]], $grouped);
    }

    public function test_groupBy_missing_key(): void
    {
        $this->expectError();
        $this->expectErrorMessage('Undefined array key "id"');
        Arr::groupBy([['dummy' => 3]], 'id'); /** @phpstan-ignore-line */
    }

    public function test_insert(): void
    {
        // empty
        $list = [];
        Arr::insert($list, 0, 'a');
        self::assertEquals(['a'], $list);

        // no values
        $list = [];
        Arr::insert($list, 0);
        self::assertEquals([], $list);

        // list: at 0
        $list = [1, 2, 3, 4];
        Arr::insert($list, 0, 'a');
        self::assertEquals(['a', 1, 2, 3, 4], $list);

        // list: at 1
        $list = [1, 2, 3, 4];
        Arr::insert($list, 1, 'a');
        self::assertEquals([1, 'a', 2, 3, 4], $list);

        // list: at 1 multiple
        $list = [1, 2, 3, 4];
        Arr::insert($list, 1, 'a', 'b');
        self::assertEquals([1, 'a', 'b', 2, 3, 4], $list);

        // list: out of range
        $list = [1, 2, 3, 4];
        Arr::insert($list, 10, 'a');
        self::assertEquals([1, 2, 3, 4, 'a'], $list);

        // list: negative
        $list = [1, 2, 3, 4];
        Arr::insert($list, -1, 'a');
        self::assertEquals([1, 2, 3, 4, 'a'], $list);

        // list: negative alt
        $list = [1, 2, 3, 4];
        Arr::insert($list, -2, 'a');
        self::assertEquals([1, 2, 3, 'a', 4], $list);

        // assoc
        $assoc = [];
        Arr::insert($assoc, 0, a: 2);
        self::assertEquals([2], $assoc);

        // assoc with index overflow
        $assoc = ['a' => 1];
        Arr::insert($assoc, 1, b: 1, c: 2);
        self::assertEquals(['a' => 1, 1, 2], $assoc);

        // assoc with list as values
        $assoc = ['a' => 1];
        Arr::insert($assoc, 1, 1);
        self::assertEquals(['a' => 1, 1], $assoc);

        // assoc duplicate key
        $assoc = ['a' => 1];
        Arr::insert($assoc, 1, a: 2);
        self::assertEquals(['a' => 1, 2], $assoc);

        // insert array
        $list = [];
        Arr::insert($list, 0, ['a']);
        self::assertEquals([['a']], $list);

    }

    public function test_intersect(): void
    {
        // empty
        self::assertEquals([], Arr::intersect([], [1]));

        // right has more keys
        self::assertEquals([1], Arr::intersect([1, 2], [1]));

        // left has more keys
        self::assertEquals([1], Arr::intersect([1], [1, 2]));

        // mixed
        self::assertEquals([2, 3], Arr::intersect([1, 2, 3], [2, 3, 4]));

        // with assoc
        self::assertEquals(['a' => 1], Arr::intersect(['a' => 1, 'b' => 2, 'c' => 3], ['b' => 1]));

        // reindex: true
        self::assertEquals([1], Arr::intersect(['a' => 1, 'b' => 2, 'c' => 3], ['b' => 1], reindex: true));

        // reindex: false
        self::assertEquals(['a' => 1], Arr::intersect(['a' => 1, 'b' => 2, 'c' => 3], ['b' => 1], reindex: false));
    }

    public function test_intersectKeys(): void
    {
        // empty left
        self::assertEquals([], Arr::intersectKeys(['a' => 1], []));

        // empty right
        self::assertEquals([], Arr::intersectKeys([], ['a' => 1]));

        // on list
        self::assertEquals([1, 2], Arr::intersectKeys([1, 2, 3], [1, 3]));

        // assoc vs list
        self::assertEquals([], Arr::intersectKeys(['a' => 1, 'b' => 2, 'c' => 3], [1]));

        // assoc (left precedence)
        self::assertEquals(['a' => 1], Arr::intersectKeys(['a' => 1, 'b' => 2, 'c' => 3], ['a' => 2]));
    }

    public function test_isAssoc(): void
    {
        // empty
        self::assertTrue(Arr::isAssoc([]));

        // on list
        self::assertFalse(Arr::isAssoc([1, 2]));

        // on assoc
        self::assertTrue(Arr::isAssoc(['a' => 1, 'b' => 2]));
        self::assertTrue(Arr::isAssoc([1 => 1, 2 => 2]));
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

    public function test_isNotEmpty(): void
    {
        // empty
        self::assertFalse(Arr::isNotEmpty([]));

        // on list
        self::assertTrue(Arr::isNotEmpty([1, 2]));

        // on assoc
        self::assertTrue(Arr::isNotEmpty(['a' => 1, 'b' => 2]));
    }

    public function test_isList(): void
    {
        self::assertTrue(Arr::isList([]));

        self::assertTrue(Arr::isList([1, 2]));

        self::assertFalse(Arr::isList(['a' => 1, 'b' => 2]));
        self::assertFalse(Arr::isList([1 => 1, 2 => 2]));
    }

    public function test_join(): void
    {
        $empty = [];
        self::assertEquals('', Arr::join($empty, ', '));
        self::assertEquals('[', Arr::join($empty, ', ', '['));
        self::assertEquals('[]', Arr::join($empty, ', ', '[', ']'));

        $list = [1, 2];
        self::assertEquals('1, 2', Arr::join($list, ', '));
        self::assertEquals('[1, 2', Arr::join($list, ', ', '['));
        self::assertEquals('[1, 2]', Arr::join($list, ', ', '[', ']'));

        $assoc = ['a' => 1, 'b' => 2];
        self::assertEquals('1, 2', Arr::join($assoc, ', '));
        self::assertEquals('[1, 2', Arr::join($assoc, ', ', '['));
        self::assertEquals('[1, 2]', Arr::join($assoc, ', ', '[', ']'));
    }

    public function test_keyBy(): void
    {
        $assoc = Arr::keyBy([1, 2], static fn($v) => 'a' . $v);
        self::assertEquals(['a1' => 1, 'a2' => 2], $assoc);

        $assoc = Arr::keyBy([['id' => 'b'], ['id' => 'c']], static fn($v): string => $v['id']);
        self::assertEquals(['b' => ['id' => 'b'], 'c' => ['id' => 'c']], $assoc);
    }

    public function test_keyBy_with_duplicate_key(): void
    {
        $this->expectException(DuplicateKeyException::class);
        Arr::keyBy([['id' => 'b'], ['id' => 'b']], static fn($v): string => $v['id']);
    }

    public function test_keyBy_with_overwritten_key(): void
    {
        $array = Arr::keyBy([['id' => 'b', 1], ['id' => 'b', 2]], static fn($v): string => $v['id'], true);
        self::assertEquals(['b' => ['id' => 'b', 2]], $array);

        $this->expectException(DuplicateKeyException::class);
        Arr::keyBy([['id' => 'b'], ['id' => 'b']], static fn(array $v): string => $v['id']);
    }

    public function test_keyBy_with_invalid_key(): void
    {
        $this->expectException(LogicException::class);
        /** @phpstan-ignore-next-line */
        Arr::keyBy([['id' => 'b', 1], ['id' => 'b', 2]], static fn($v) => false);
    }

    public function test_keys(): void
    {
        // empty
        self::assertEquals([], Arr::keys([]));

        // list
        self::assertEquals([0,1], Arr::keys([1, 2]));

        // assoc
        self::assertEquals(['a', 'b'], Arr::keys(['a' => 1, 'b' => 2]));
    }

    public function test_last(): void
    {
        // empty
        self::assertEquals(null, Arr::last([]));

        // no condition matched
        self::assertEquals(null, Arr::last([1, 2], static fn(int $i) => $i > 2));

        // with no condition
        self::assertEquals(20, Arr::last([10, 20]));

        // condition matched
        self::assertEquals(20, Arr::last([10, 20], static fn($v, $k) => $k === 1));
        self::assertEquals(20, Arr::last([10, 20], static fn($v, $k) => $v === 20));

        // with assoc
        self::assertEquals(20, Arr::last(['a' => 10, 'b' => 20]));
        self::assertEquals(20, Arr::last(['a' => 10, 'b' => 20, 'c' => 30], static fn($v, $k) => $k === 'b'));
    }

    public function test_lastIndex(): void
    {
        // empty
        self::assertEquals(null, Arr::lastIndex([]));

        // empty with condition
        self::assertEquals(null, Arr::lastIndex([], static fn($v, $k) => true));

        // no condition
        self::assertEquals(2, Arr::lastIndex([10, 20, 20]));

        // with condition
        self::assertEquals(1, Arr::lastIndex([10, 20, 20], static fn($v, $k) => $k === 1));
        self::assertEquals(2, Arr::lastIndex([10, 20, 20], static fn($v, $k) => $v === 20));

        // no condition matched
        self::assertEquals(null, Arr::lastIndex([10, 20, 20], static fn() => false));

        // with assoc
        self::assertEquals(1, Arr::lastIndex(['a' => 10, 'b' => 20]));
        self::assertEquals(1, Arr::lastIndex(['a' => 10, 'b' => 20, 'c' => 30], static fn($v, $k) => $k === 'b'));
    }

    public function test_lastKey(): void
    {
        // empty array returns null
        self::assertEquals(null, Arr::lastKey([]));

        $list = [10, 20, 20];

        // list: get last key (index)
        self::assertEquals(2, Arr::lastKey($list));

        // list: get last match on condition
        self::assertEquals(2, Arr::lastKey($list, static fn($v, $k) => $v === 20));

        // list: no match returns null
        self::assertEquals(null, Arr::lastKey($list, static fn($v, $k) => false));

        $assoc = ['a' => 10, 'b' => 20, 'c' => 20];

        // assoc: get last key
        self::assertEquals('c', Arr::lastKey($assoc));

        // assoc: match on key condition
        self::assertEquals('b', Arr::lastKey($assoc, static fn($v, $k) => in_array($k, ['a','b'], true)));

        // assoc: match on last condition matched
        self::assertEquals('c', Arr::lastKey($assoc, static fn($v, $k) => $v === 20));

        // assoc: no match returns null
        self::assertEquals(null, Arr::lastKey($assoc, static fn() => false));
    }

    public function test_lastOr(): void
    {
        $miss = Nil::instance();

        // empty
        self::assertEquals($miss, Arr::lastOr([], $miss));

        self::assertEquals(20, Arr::lastOr([10, 20], $miss));
        self::assertEquals(20, Arr::lastOr([10, 20], $miss, static fn($v, $k) => $k === 1));
        self::assertEquals(20, Arr::lastOr([10, 20], $miss, static fn($v, $k) => $v === 20));
        self::assertEquals($miss, Arr::lastOr([10, 20], $miss, static fn() => false));
    }

    public function test_lastOrFail_empty(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Iterable must contain at least one element.');
        Arr::lastOrFail([]);
    }

    public function test_lastOrFail_bad_condition(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Failed to find matching condition.');
        Arr::lastOrFail([1,2], static fn(int $i) => $i > 2);
    }

    public function test_map(): void
    {
        // empty
        self::assertEquals([], Arr::map([], static fn($i) => true));

        // 1st argument contains values
        self::assertEquals([2, 4, 6], Arr::map([1, 2, 3], static fn($i) => $i * 2));

        // 2nd argument contains keys
        self::assertEquals([0, 1, 2], Arr::map([1, 2, 3], static fn($i, $k) => $k));

        // assoc: retains key
        self::assertEquals(['a' => 2, 'b' => 4], Arr::map(['a' => 1, 'b' => 2], static fn($i) => $i * 2));
    }

    public function test_max(): void
    {
        // list
        self::assertEquals(10, Arr::max([1, 2, 3, 10, 1]));
        self::assertEquals(100, Arr::max([100, 2, 3, 10, 1]));
        self::assertEquals(90, Arr::max([1, 2, 3, 10, 1, 90, -100]));

        // assoc
        self::assertEquals(100, Arr::max(['a' => 1, 'b' => 100, 'c' => 10]));

        // max by value
        self::assertEquals(2, Arr::max(['a' => 2, 'b' => 1], static fn($v, $k) => $v));

        // max by key
        self::assertEquals(1, Arr::max(['a' => 2, 'b' => 1], static fn($v, $k) => $k));
    }

    public function test_max_with_empty(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('$iterable must contain at least one value');
        Arr::max([]);
    }

    public function test_merge(): void
    {
        // empty
        self::assertEquals([], Arr::merge([], []));

        // empty left
        self::assertEquals([1, [2]], Arr::merge([], [1, [2]]));

        // merge list
        self::assertEquals([1, 2, 3, 4], Arr::merge([1, 2], [3, 4]));

        // merge assoc
        self::assertEquals([1, 2, 3, 'a' => 4], Arr::merge([1, 2], [3, 'a' => 4]));

        // merge assoc with list
        self::assertEquals(['0' => 1, 1, [2]], Arr::merge(['0' => 1], [1, [2]]));

        // merge list with assoc
        self::assertEquals([1, [2], 'a' => 1], Arr::merge([1, [2]], ['a' => 1]));

        // latter array takes precedence
        self::assertEquals(['a' => [3]], Arr::merge(['a' => [1, 2]], ['a' => [3]]));
    }

    public function test_mergeRecursive(): void
    {
        // empty
        self::assertEquals([], Arr::mergeRecursive([], []));

        // basic merge list
        self::assertEquals([1, 2, 3], Arr::mergeRecursive([1, 2], [3]));

        // basic merge assoc
        self::assertEquals(['a' => 1, 'b' => 2], Arr::mergeRecursive(['a' => 1], ['b' => 2]));

        // latter takes precedence
        self::assertEquals(['a' => 2], Arr::mergeRecursive(['a' => 1], ['a' => 2]));

        // don't mix value types like array_merge
        self::assertEquals(['a' => ['c' => 1]], Arr::mergeRecursive(['a' => 1], ['a' => ['c' => 1]]));

        // merge inner arrays
        self::assertEquals(['a' => [1, 2, 'c' => 1]], Arr::mergeRecursive(['a' => [1, 2]], ['a' => ['c' => 1]]));

        // complex merge
        $merged = Arr::mergeRecursive(['a' => ['b' => 1], 'd' => 4], ['a' => ['c' => 2], 'b' => 3]);
        self::assertEquals(['a' => ['b' => 1, 'c' => 2], 'b' => 3, 'd' => 4], $merged);
    }

    public function test_min(): void
    {
        // list
        self::assertEquals(0, Arr::min([1, 2, 3, 0, 1]));
        self::assertEquals(-100, Arr::min([-100, 2, 3, 10, 1]));
        self::assertEquals(-90, Arr::min([1, 2, 3, 10, 1, -90, 100]));
        self::assertEquals(-100, Arr::min([1, 2, 3, 10, 1, 90, -100]));

        // assoc
        self::assertEquals(1, Arr::min(['a' => 100, 'b' => 10, 'c' => 1]));

        // min by value
        self::assertEquals(1, Arr::min(['a' => 2, 'b' => 1], static fn($v, $k) => $v));

        // min by key
        self::assertEquals(2, Arr::min(['a' => 2, 'b' => 1], static fn($v, $k) => $k));

        // value with condition
        self::assertEquals(1, Arr::min(['a' => 2, 'b' => 1], static fn($v, $k) => $v));

        // key with condition
        self::assertEquals(2, Arr::min(['a' => 2, 'b' => 1], static fn($v, $k) => $k));
    }

    public function test_min_with_empty(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('$iterable must contain at least one value');
        Arr::min([]);
    }

    public function test_minMax(): void
    {
        // only one array
        self::assertEquals(['min' => 1, 'max' => 1], Arr::minMax([1]));

        // basic usage
        self::assertEquals(['min' => -100, 'max' => 10], Arr::minMax([1, 10, -100]));

        // with condition list
        self::assertEquals(['min' => 1, 'max' => 2], Arr::minMax([2, 1], static fn($v, $k) => $v));

        // with condition assoc
        self::assertEquals(['min' => 1, 'max' => 2], Arr::minMax(['a' => 2, 'b' => 1], static fn($v, $k) => $v));
    }

    public function test_minMax_empty(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('$iterable must contain at least one value');
        Arr::minMax([]);
    }

    public function test_notContains(): void
    {
        self::assertTrue(Arr::notContains([], 0));
        self::assertTrue(Arr::notContains([], null));
        self::assertTrue(Arr::notContains([], []));
        self::assertTrue(Arr::notContains([null, 0], false));
        self::assertTrue(Arr::notContains([null, 0], 1));
        self::assertTrue(Arr::notContains(['a' => 1], 'a'));
        self::assertFalse(Arr::notContains([null, 0], null));
        self::assertFalse(Arr::notContains([null, []], []));
        self::assertFalse(Arr::notContains(['a' => 1, 0], 1));
    }

    public function test_notContainsKey(): void
    {
        self::assertTrue(Arr::notContainsKey([], 0));
        self::assertTrue(Arr::notContainsKey([], 1));
        self::assertTrue(Arr::notContainsKey(['b' => 1], 'a'));
        self::assertFalse(Arr::notContainsKey([1], 0));
        self::assertFalse(Arr::notContainsKey([11 => 1], 11));
        self::assertFalse(Arr::notContainsKey(['a' => 1, 0], 'a'));
    }

    public function test_only(): void
    {
        // with list array
        self::assertEquals([2], Arr::only([1, 2, 3], [1]));

        // with assoc array
        self::assertEquals(['a' => 1, 'b' => 2], Arr::only(['a' => 1, 'b' => 2, 'c' => 3], ['a', 'b']));

        // different order of keys
        self::assertEquals(['c' => 3, 'b' => 2], Arr::only(['a' => 1, 'b' => 2, 'c' => 3], ['c', 'b']));

        // different order of keys
        self::assertEquals(['c' => 3, 'b' => 2], Arr::only(['a' => 1, 'b' => 2, 'c' => 3], ['x' => 'c', 'b']));
    }

    public function test_only_WithUndefinedKey(): void
    {
        $this->expectError();
        $this->expectErrorMessage('Undefined array key "a"');
        self::assertEquals([], Arr::only([], ['a']));
    }

    public function test_pop(): void
    {
        // empty
        $list = [];
        self::assertEquals(null, Arr::pop($list));

        // list
        $list = [1, 2];
        self::assertEquals(2, Arr::pop($list));
        self::assertEquals([1], $list);

        // assoc
        $assoc = ['a' => 1, 'b' => 2];
        self::assertEquals(2, Arr::pop($assoc));
        self::assertEquals(['a' => 1], $assoc);
    }

    public function test_popMany(): void
    {
        // empty
        $list = [];
        self::assertEquals([], Arr::popMany($list, 1));
        self::assertEquals([], $list);

        // list: 1
        $list = [1, 2];
        self::assertEquals([2], Arr::popMany($list, 1));
        self::assertEquals([1], $list);

        // list: 2
        $list = [1, 2];
        self::assertEquals([1, 2], Arr::popMany($list, 2));
        self::assertEquals([], $list);

        // assoc: 1
        $assoc = ['a' => 1, 'b' => 2];
        self::assertEquals(['b' => 2], Arr::popMany($assoc, 1));
        self::assertEquals(['a' => 1], $assoc);

        // assoc: 2
        $assoc = ['a' => 1, 'b' => 2];
        self::assertEquals(['a' => 1, 'b' => 2], Arr::popMany($assoc, 2));
        self::assertEquals([], $assoc);
    }

    public function test_popMany_zero_amount(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Expected a value greater than 0. Got: 0');
        $list = [1, 2];
        self::assertEquals([], Arr::popMany($list, 0));
    }

    public function test_popMany_negative_amount(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Expected a value greater than 0. Got: -1');
        $list = [1, 2];
        self::assertEquals([], Arr::popMany($list, -1));
    }

    public function test_prioritize(): void
    {
        // empty
        self::assertEquals([], Arr::prioritize([], static fn() => true));

        // no change
        $prioritized = Arr::prioritize([1, 2, 3], static fn() => false);
        self::assertEquals([1, 2, 3], $prioritized);

        // list
        $prioritized = Arr::prioritize([1, 2, 3], static fn(int $i) => $i === 2);
        self::assertEquals([2, 1, 3], $prioritized);

        // assoc
        $prioritized = Arr::prioritize(['a' => 1, 'bc' => 2, 'de' => 2, 'b' => 2], static fn($_, string $k) => strlen($k) > 1);
        self::assertEquals(['bc', 'de', 'a', 'b'], Arr::keys($prioritized));

        // reindex: true
        $prioritized = Arr::prioritize(['a' => 1, 'bc' => 2, 'de' => 2, 'b' => 2], static fn($_, string $k) => strlen($k) > 1, reindex: true);
        self::assertEquals([0, 1, 2, 3], Arr::keys($prioritized));

        // reindex: false
        $prioritized = Arr::prioritize([1, 2, 3, 4], static fn($_, int $k) => $k > 1, reindex: true);
        self::assertEquals([0, 1, 2, 3], Arr::keys($prioritized));
    }

    public function test_pull(): void
    {
        // empty
        $list = [];
        self::assertEquals(null, Arr::pull($list, 1));

        // list
        $list = [1, 2, 3];
        self::assertEquals(2, Arr::pull($list, 1));
        self::assertEquals([1, 3], $list);

        // list: non-existent key
        $list = [1, 2, 3];
        self::assertEquals(null, Arr::pull($list, 4));

        // assoc
        $assoc = ['a' => 1, 'b' => 2];
        self::assertEquals(2, Arr::pull($assoc, 'b'));
        self::assertEquals(['a' => 1], $assoc);

        // assoc: non-existent key
        $assoc = ['a' => 1, 'b' => 2];
        self::assertEquals(null, Arr::pull($assoc, 'c'));

        // reindex: false
        $list = [1, 2, 3];
        self::assertEquals(2, Arr::pull($list, 1, false));
        self::assertEquals([0 => 1, 2 => 3], $list);

        // reindex: true
        $assoc = ['a' => 1, 'b' => 2];
        self::assertEquals(1, Arr::pull($assoc, 'a', true));
        self::assertEquals([2], $assoc);
    }

    public function test_pullOr(): void
    {
        $nil = Nil::instance();

        // empty
        $list = [];
        self::assertEquals('_test_', Arr::pullOr($list, 1, '_test_'));

        // list
        $list = [1, 2, 3];
        self::assertEquals(2, Arr::pullOr($list, 1, null));
        self::assertEquals([1, 3], $list);

        // list miss
        $list = [1, 2, 3];
        self::assertEquals($nil, Arr::pullOr($list, 3, $nil));
        self::assertEquals([1, 2, 3], $list);

        // assoc
        $assoc = ['a' => 1, 'b' => 2];
        self::assertEquals(2, Arr::pullOr($assoc, 'b', null));
        self::assertEquals(['a' => 1], $assoc);

        // assoc miss
        $assoc = ['a' => null];
        self::assertEquals($nil, Arr::pullOr($assoc, 'b', $nil));
        self::assertEquals(['a' => null], $assoc);

        // reindex: false
        $list = [1, 2, 3];
        self::assertEquals(2, Arr::pullOr($list, 1, null, false));
        self::assertEquals([0 => 1, 2 => 3], $list);

        // reindex: true
        $assoc = ['a' => 1, 'b' => 2];
        self::assertEquals(2, Arr::pullOr($assoc, 'b', null, true));
        self::assertEquals([1], $assoc);
    }

    public function test_pullOrFail(): void
    {
        // list
        $list = [1, 2, 3];
        self::assertEquals(2, Arr::pullOrFail($list, 1));
        self::assertEquals([1, 3], $list);

        // assoc
        $assoc = ['a' => 1, 'b' => 2];
        self::assertEquals(2, Arr::pullOrFail($assoc, 'b'));
        self::assertEquals(['a' => 1], $assoc);
    }

    public function test_pullOrFail_pull_on_empty(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Tried to pull undefined array key "1"');
        $empty = [];
        Arr::pullOrFail($empty, 1);
    }

    public function test_pullOrFail_pull_undefined_key(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Tried to pull undefined array key "c"');
        $assoc = ['a' => 1, 'b' => 2];
        Arr::pullOrFail($assoc, 'c');
    }

    public function test_pullMany(): void
    {
        // empty
        $list = [];
        $pulled = Arr::pullMany($list, [0, 1, 'a']);
        self::assertEquals([], $list);
        self::assertEquals([], $pulled);

        // list
        $list = [1, 2, 3];
        $pulled = Arr::pullMany($list, [0, 1]);
        self::assertEquals([3], $list);
        self::assertEquals([1, 2], $pulled);

        // assoc
        $assoc = ['a' => 1, 'b' => 2, 'c' => 3];
        $pulled = Arr::pullMany($assoc, ['a', 'c']);
        self::assertEquals(['b' => 2], $assoc);
        self::assertEquals(['a' => 1, 'c' => 3], $pulled);

        // assoc: miss some key
        $assoc = ['a' => 1];
        $pulled = Arr::pullMany($assoc, ['a', 'c']);
        self::assertEquals([], $assoc);
        self::assertEquals(['a' => 1], $pulled);
    }

    public function test_reduce(): void
    {
        // reduce with keys
        $reduced = Arr::reduce([0 => 0, 1 => 0, 2 => 0], static fn(int $c, $i, $k) => $c + $k);
        self::assertEquals(3, $reduced);

        // reduce with values
        $reduced = Arr::reduce([1, 2, 3], static fn(int $c, $i, $k) => $c + $i);
        self::assertEquals(6, $reduced);

        // assoc only one element
        $reduced = Arr::reduce(['a' => 1], static fn(int $c, $i, $k) => -100);
        self::assertEquals(1, $reduced);

        // assoc more than one element
        $reduced = Arr::reduce(['a' => 1, 'b' => 2], static fn($val, $i) => $i * 2);
        self::assertEquals(4, $reduced);
    }

    public function test_reduce_unable_to_guess_initial(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Iterable must contain at least one element.');
        Arr::reduce([], static fn($c, $i, $k) => $k);
    }

    public function test_remove(): void
    {
        // empty
        $list = [];
        self::assertEquals([], Arr::remove($list, 1));
        self::assertEquals([], $list);

        // list
        $list = [1, 2, 1];
        self::assertEquals([0, 2], Arr::remove($list, 1));
        self::assertEquals([2], $list);

        // list with limit
        $list = [1, 2, 1];
        self::assertEquals([0], Arr::remove($list, 1, 1));
        self::assertEquals([2, 1], $list);

        // assoc
        $assoc = ['a' => 1, 'b' => 2, 'c' => 1];
        self::assertEquals(['a', 'c'], Arr::remove($assoc, 1));
        self::assertEquals(['b' => 2], $assoc);

        // assoc with limit
        $assoc = ['a' => 1, 'b' => 2, 'c' => 1];
        self::assertEquals(['a'], Arr::remove($assoc, 1, 1));
        self::assertEquals(['b' => 2, 'c' => 1], $assoc);

        // reindex: false
        $assoc = [1, 2, 1];
        self::assertEquals([0, 2], Arr::remove($assoc, 1, reindex: false));
        self::assertEquals([1 => 2], $assoc);

        // reindex: true
        $assoc = ['a' => 1, 'b' => 2, 'c' => 1];
        self::assertEquals(['a', 'c'], Arr::remove($assoc, 1, reindex: true));
        self::assertEquals([2], $assoc);
    }

    public function test_removeKey(): void
    {
        // empty
        $list = [];
        self::assertFalse(Arr::removeKey($list, 1));
        self::assertEquals([], $list);

        // list: hit
        $list = [1, 2, 3];
        self::assertTrue(Arr::removeKey($list, 1));
        self::assertEquals([1, 3], $list);

        // list: miss
        $list = [1, 2, 3];
        self::assertFalse(Arr::removeKey($list, 3));
        self::assertEquals([1, 2, 3], $list);

        // assoc: hit
        $assoc = ['a' => 1, 'b' => 2, 'c' => 3];
        self::assertTrue(Arr::removeKey($assoc, 'b'));
        self::assertEquals(['a' => 1, 'c' => 3], $assoc);

        // assoc: miss
        $assoc = ['a' => 1, 'b' => 2, 'c' => 3];
        self::assertFalse(Arr::removeKey($assoc, 'd'));
        self::assertEquals(['a' => 1, 'b' => 2 , 'c' => 3], $assoc);

        // reindex: false
        $list = [1, 2, 3];
        self::assertTrue(Arr::removeKey($list, 1, false));
        self::assertEquals([0 => 1, 2 => 3], $list);

        // reindex: true
        $assoc = ['a' => 1, 'b' => 2, 'c' => 3];
        self::assertTrue(Arr::removeKey($assoc, 'b', true));
        self::assertEquals([1, 3], $assoc);
    }

    public function test_repeat(): void
    {
        // empty
        self::assertEquals([], Arr::repeat([], 1));

        // Repeat single 3 times
        self::assertEquals([1, 1, 1], Arr::repeat([1], 3));

        // Repeat multiple 3 times
        self::assertEquals([1, 2, 1, 2], Arr::repeat([1, 2], 2));

        // Repeat hash 3 times (loses the keys)
        self::assertEquals([1, 2, 1, 2], Arr::repeat(['a' => 1, 'b' => 2], 2));

        // Repeat 0 times (does nothing)
        self::assertEquals([], Arr::repeat([1], 0));
    }

    public function test_repeat_negative_times(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Expected a value greater than or equal to 0. Got: -1');
        self::assertEquals([], Arr::repeat([1], -1)); /** @phpstan-ignore-line */
    }

    public function test_reverse(): void
    {
        // empty
        self::assertEquals([], Arr::reverse([]));

        // list
        self::assertEquals([2, 1], Arr::reverse([1, 2]));

        // assoc with int
        self::assertEquals([200 => 2, 100 => 1], Arr::reverse([100 => 1, 200 => 2]));

        // assoc
        self::assertEquals(['b' => 2, 'a' => 1], Arr::reverse(['a' => 1, 'b' => 2]));
        self::assertEquals(['b', 'a'], array_keys(Arr::reverse(['a' => 1, 'b' => 2])));

        // assoc with int
        self::assertEquals([2, 'a' => 1], Arr::reverse(['a' => 1, 2]));

        // reindex: true
        self::assertEquals([2, 1], Arr::reverse([1, 2], true));
        self::assertEquals([0, 1], array_keys(Arr::reverse([1, 2], true)));

        // reindex: false
        self::assertEquals([1 => 2, 0 => 1], Arr::reverse([1, 2], false));
        self::assertEquals([1, 0], array_keys(Arr::reverse([1, 2], false)));
    }

    public function test_rotate(): void
    {
        // empty
        self::assertEquals([], array_keys(Arr::rotate([], 1)));

        // none
        self::assertEquals([], array_keys(Arr::rotate([1], 0)));

        // once
        self::assertEquals(['b', 'c', 'a'], array_keys(Arr::rotate(['a' => 1, 'b' => 2, 'c' => 3], 1)));

        // twice
        self::assertEquals(['c' => 3, 'a' => 1, 'b' => 2], Arr::rotate(['a' => 1, 'b' => 2, 'c' => 3], 2));

        // negative once
        self::assertEquals(['c', 'a', 'b'], array_keys(Arr::rotate(['a' => 1, 'b' => 2, 'c' => 3], -1)));

        // negative twice
        self::assertEquals(['b', 'c', 'a'], array_keys(Arr::rotate(['a' => 1, 'b' => 2, 'c' => 3], -2)));

        // list
        self::assertEquals([2, 3, 1], Arr::rotate([1, 2, 3], 1));
        self::assertEquals([3, 1, 2], Arr::rotate([1, 2, 3], 2));
        self::assertEquals([1, 2, 3], Arr::rotate([1, 2, 3], 3));

        // reindex: true on list
        self::assertEquals([2, 3, 1], Arr::rotate([1, 2, 3], 1, reindex: true));

        // reindex: true on assoc
        self::assertEquals([3, 1, 2], Arr::rotate(['a' => 1, 'b' => 2, 'c' => 3], 2, reindex: true));

        // reindex: false on list
        self::assertEquals([1, 2, 0], array_keys(Arr::rotate([1, 2, 3], 1, reindex: false)));

        // reindex: false on assoc
        self::assertEquals(['c' => 3, 'a' => 1, 'b' => 2], Arr::rotate(['a' => 1, 'b' => 2, 'c' => 3], 2, reindex: false));
    }

    public function test_sample(): void
    {
        mt_srand(100);
        self::assertEquals(8, Arr::sample(range(0, 10)));
    }

    public function test_sample_Empty(): void
    {
        $this->expectError();
        $this->expectErrorMessage('array_rand(): Argument #1 ($array) cannot be empty');
        Arr::sample([]);
    }

    public function test_sampleMany(): void
    {
        mt_srand(100);
        self::assertEquals([8, 9], Arr::sampleMany(range(0, 10), 2));
        self::assertEquals(['a' => 0, 'd' => 3], Arr::sampleMany(['a' => 0, 'b' => 1, 'c' => 2, 'd' => 3], 2));

        // reindex: true
        mt_srand(100);
        self::assertEquals([8, 9], Arr::sampleMany(range(0, 10), 2, reindex: true));
        self::assertEquals([0, 3], Arr::sampleMany(['a' => 0, 'b' => 1, 'c' => 2, 'd' => 3], 2, reindex: true));

        // reindex: false
        mt_srand(100);
        self::assertEquals([8 => 8, 9 => 9], Arr::sampleMany(range(0, 10), 2, reindex: false));
        self::assertEquals(['a' => 0, 'd' => 3], Arr::sampleMany(['a' => 0, 'b' => 1, 'c' => 2, 'd' => 3], 2, reindex: false));
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

    public function test_shuffle(): void
    {
        mt_srand(100);
        self::assertEquals([1, 2, 4, 3, 2], Arr::shuffle([1, 2, 2, 3, 4]));
        self::assertEquals(['a' => 1, 'c' => 3, 'b' => 2, 'd' => 4], Arr::shuffle(['a' => 1, 'b' => 2, 'c' => 3, 'd' => 4]));

        // reindex: false
        mt_srand(100);
        self::assertEquals([0, 2, 4, 3, 1], array_keys(Arr::shuffle([1, 2, 2, 3, 4], reindex: false)));
        self::assertEquals(['a' => 1, 'c' => 3, 'b' => 2, 'd' => 4], Arr::shuffle(['a' => 1, 'b' => 2, 'c' => 3, 'd' => 4], reindex: false));

        // reindex: true
        mt_srand(100);
        self::assertEquals([0, 1, 2, 3, 4], array_keys(Arr::shuffle([1, 2, 2, 3, 4], reindex: true)));
        self::assertEquals([0, 1, 2, 3], array_keys(Arr::shuffle(['a' => 1, 'b' => 2, 'c' => 3, 'd' => 4], reindex: true)));
    }

    public function test_slice(): void
    {
        // with offset
        self::assertEquals([2, 3], Arr::slice([1, 2, 3], 1));

        // with negative offset
        self::assertEquals([2, 3], Arr::slice([1, 2, 3], -2));

        // with overflow offset
        self::assertEquals([], Arr::slice([1, 2, 3], 100));

        // with length
        self::assertEquals([2, 3], Arr::slice([1, 2, 3], 1, 2));

        // with negative length
        self::assertEquals([1, 2], Arr::slice([1, 2, 3], 0, -1));

        // with overflow length
        self::assertEquals([2, 3], Arr::slice([1, 2, 3], 1, 100));

        // assoc
        self::assertEquals(['b' => 2, 'c' => 3], Arr::slice(['a' => 1, 'b' => 2, 'c' => 3], 1, 2));

        // reindex: true
        self::assertEquals([2], Arr::slice(['a' => 1, 'b' => 2, 'c' => 3], 1, 1, reindex: true));

        // reindex: false
        self::assertEquals([1 => 2], Arr::slice([1, 2, 3], 1, 1, reindex: false));
    }

    public function test_sole(): void
    {
        // list
        self::assertEquals(1, Arr::sole([1]));

        // assoc
        self::assertEquals(1, Arr::sole(['a' => 1]));

        // with condition
        self::assertEquals(2, Arr::sole([1, 2, 3],  static fn(int $i) => $i === 2));
    }

    public function test_sole_zero_item(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Expected only one element in result. 0 given.');
        Arr::sole([]);
    }

    public function test_sole_more_than_one_item(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Expected only one element in result. 2 given.');
        Arr::sole([1, 2]);
    }

    public function test_sort(): void
    {
        // empty
        self::assertEquals([], Arr::sort([]));

        // list
        self::assertEquals([1, 2, 3, 4], Arr::sort([4, 2, 1, 3]));

        // assoc
        self::assertEquals(['b' => 1, 'c' => 2, 'a' => 3], Arr::sort(['a' => 3, 'b' => 1, 'c' => 2]));

        // with SORT_NATURAL flag
        self::assertEquals(['2', '30', '100'], Arr::sort(['30', '2', '100'], flag: SORT_NATURAL));

        // list: with condition
        self::assertEquals([1, 2, 3, 4], Arr::sort([4, 2, 1, 3], static fn($v) => $v));

        // assoc: with condition
        self::assertEquals(['a' => 1, 'b' => 0, 'c' => 2], Arr::sort(['b' => 0, 'a' => 1, 'c' => 2], static fn($v, $k) => $k));

        // reindex: true
        self::assertEquals([1, 2, 3], Arr::sort(['a' => 3, 'b' => 1, 'c' => 2], reindex: true));

        // reindex: false
        self::assertEquals(['b' => 1, 'c' => 2, 'a' => 3], Arr::sort(['a' => 3, 'b' => 1, 'c' => 2], reindex: false));
    }

    public function test_sortDesc(): void
    {
        // empty
        self::assertEquals([], Arr::sortDesc([]));

        // list
        self::assertEquals([4, 3, 2, 1], Arr::sortDesc([4, 2, 1, 3]));

        // assoc
        self::assertEquals(['a' => 3, 'c' => 2, 'b' => 1], Arr::sortDesc(['a' => 3, 'b' => 1, 'c' => 2]));

        // with SORT_NATURAL flag
        self::assertEquals(['100', '30', '2'], Arr::sortDesc(['30', '100', '2'], flag: SORT_NATURAL));

        // list: with condition
        self::assertEquals([4, 3, 2, 1], Arr::sortDesc([4, 2, 1, 3], static fn($v) => $v));

        // assoc: with condition
        self::assertEquals(['c' => 2, 'b' => 0, 'a' => 1], Arr::sortDesc(['b' => 0, 'a' => 1, 'c' => 2], static fn($v, $k) => $k));

        // reindex: true
        $sorted = Arr::sortDesc(['a' => 3, 'b' => 1, 'c' => 2], reindex: true);
        self::assertEquals([0, 1, 2], array_keys($sorted));
        self::assertEquals([3, 2, 1], array_values($sorted));

        // reindex: false
        $sorted = Arr::sortDesc(['a' => 3, 'b' => 1, 'c' => 2], reindex: false);
        self::assertEquals(['a', 'c', 'b'], array_keys($sorted));
        self::assertEquals([3, 2, 1], array_values($sorted));
    }

    public function test_sortByKeys(): void
    {
        // empty
        self::assertEquals([], Arr::sortByKey([]));

        // list stays the same
        self::assertEquals([0, 1, 2], Arr::sortByKey([0, 1, 2]));

        // assoc
        $assoc = Arr::sortByKey(['b' => 0, 'a' => 1, 'c' => 2]);
        self::assertEquals(['a' => 1, 'b' => 0, 'c' => 2], $assoc);

        // with SORT_NATURAL
        $assoc = Arr::sortByKey(['2' => 0, '100' => 1, '30' => 2], SORT_NATURAL);
        self::assertEquals(['2' => 0, '30' => 2, '100' => 1], $assoc);
    }

    public function test_sortByKeysDesc(): void
    {
        // empty
        self::assertEquals([], Arr::sortByKeyDesc([]));

        // list stays the same
        self::assertEquals([2, 1, 0], array_keys(Arr::sortByKeyDesc([0, 1, 2])));

        // assoc
        $assoc = Arr::sortByKeyDesc(['b' => 0, 'a' => 1, 'c' => 2]);
        self::assertEquals(['c' => 2, 'b' => 0, 'a' => 1], $assoc);

        // with SORT_NATURAL
        $assoc = Arr::sortByKeyDesc(['2' => 0, '100' => 1, '30' => 2], SORT_NATURAL);
        self::assertEquals(['100' => 1, '30' => 2, '2' => 0], $assoc);
    }

    public function test_sortWith(): void
    {
        // empty
        self::assertEquals([], Arr::sortWith([], static fn($a, $b) => 1));

        // list
        $list = Arr::sortWith([1, 3, 2], static fn($a, $b) => ($a < $b) ? -1 : 1);
        self::assertEquals([1, 2, 3], $list);

        // assoc
        $assoc = Arr::sortWith(['b' => 1, 'a' => 3, 'c' => 2], static fn($a, $b) => ($a < $b) ? -1 : 1);
        self::assertEquals(['b' => 1, 'c' => 2, 'a' => 3], $assoc);
    }

    public function test_sortWithKey(): void
    {
        $assoc = Arr::sortWithKey([1 => 'a', 3 => 'b', 2 => 'c'], static fn($a, $b) => ($a < $b) ? -1 : 1);
        self::assertEquals([1 => 'a', 2 => 'c', 3 => 'b'], $assoc);
    }

    public function test_sum(): void
    {
        // empty
        self::assertEquals(0, Arr::sum([]));

        // int
        self::assertEquals(3, Arr::sum([1, 1, 1]));

        // float
        self::assertEquals(0.3, Arr::sum([0.1, 0.2]));

        // assoc
        self::assertEquals(6, Arr::sum(['b' => 1, 'a' => 3, 'c' => 2]));
    }

    public function test_sum_throw_on_sum_of_string(): void
    {
        $this->expectError();
        $this->expectErrorMessage('Unsupported operand types: int + string');
        Arr::sum(['a', 'b']);
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
        self::assertEquals(['b' => 3, 1], $result);
        self::assertEquals([3, 1], Arr::values($result));

        // reindex: true
        $result = Arr::symDiff(['a' => 2, 'b' => 3], [2, 1], reindex: true);
        self::assertEquals([3, 1], $result);

        // reindex: false
        $result = Arr::symDiff(['a' => 2, 'b' => 3], [2, 1], reindex: false);
        self::assertEquals(['b' => 3, 1], $result);
        self::assertEquals([3, 1], Arr::values($result));

        // with custom diff subject
        $diff = Arr::symDiff([[1], [2]], [[2], [3]], by: static fn(array $a, array $b) => $a[0] <=> $b[0]);
        self::assertEquals([[1], [3]], $diff);
    }

    public function test_take(): void
    {
        // empty
        self::assertEquals([], Arr::take([], 1));

        // zero take
        self::assertEquals([], Arr::take([2, 3, 4], 0));

        // list
        self::assertEquals([2, 3], Arr::take([2, 3, 4], 2));

        // assoc
        self::assertEquals(['b' => 1], Arr::take(['b' => 1, 'a' => 3, 'c' => 2], 1));

        // amount over count
        self::assertEquals([], Arr::take([], 100));
    }

    public function test_take_fail_on_negative_amount(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Expected a value greater than or equal to 0. Got: -1');
        Arr::take(['a' => 1], -1);
    }

    public function test_takeUntil(): void
    {
        // empty
        self::assertEquals([], Arr::takeUntil([], static fn($v) => false));

        // list
        $list = Arr::takeUntil([1, 1, 3, 2], static fn($v) => $v > 2);
        self::assertEquals([1, 1], $list);

        // assoc
        $assoc = Arr::takeUntil(['b' => 1, 'a' => 3, 'c' => 2], static fn($v) => $v > 2);
        self::assertEquals(['b' => 1], $assoc);

        // all false
        $assoc = Arr::takeUntil(['b' => 1, 'a' => 3, 'c' => 2],  static fn($v) => false);
        self::assertEquals(['b' => 1, 'a' => 3, 'c' => 2], $assoc);

        // all true
        $assoc = Arr::takeUntil(['b' => 1, 'a' => 3, 'c' => 2], static fn($v) => true);
        self::assertEquals([], $assoc);
    }

    public function test_takeWhile(): void
    {
        // empty
        self::assertEquals([], Arr::takeUntil([], static fn($v) => false));

        // list
        self::assertEquals([1, 1], Arr::takeWhile([1, 1, 3, 2], static fn($v) => $v <= 2));

        // assoc
        $assoc = Arr::takeWhile(['b' => 1, 'a' => 3, 'c' => 4], static fn($v) => $v < 4);
        self::assertEquals(['b' => 1, 'a' => 3], $assoc);

        // all false
        $assoc = Arr::takeWhile(['b' => 1, 'a' => 3, 'c' => 2], static fn($v) => false);
        self::assertEquals([], $assoc);

        // all true
        $assoc = Arr::takeWhile(['b' => 1, 'a' => 3, 'c' => 2], static fn($v) => true);
        self::assertEquals(['b' => 1, 'a' => 3, 'c' => 2], $assoc);
    }

    public function test_toUrlQuery(): void
    {
        // basic usage
        self::assertEquals("a=1&b=2", Arr::toUrlQuery(['a' => 1, 'b' => 2]));

        // with namespace
        self::assertEquals(urlencode('t[a]').'=1', Arr::toUrlQuery(['a' => 1], 't'));

        // with space as %20
        self::assertEquals(urlencode('t[a]').'=a%20b', Arr::toUrlQuery(['a' => 'a b'], 't'));
    }

    public function test_unique(): void
    {
        // empty
        self::assertEquals([], Arr::unique([]));

        // list
        self::assertEquals([1, 2], Arr::unique([1, 1, 2, 2]));

        // assoc
        self::assertEquals(['a' => 1, 'b' => 2], Arr::unique(['a' => 1, 'b' => 2, 'c' => 2]));

        // don't convert to string like array_unique
        $values = ['3', 3, null, '', 0, true, false];
        self::assertEquals($values, Arr::unique(Arr::repeat($values, 2)));

        // reindex: true
        self::assertEquals([1, 2], Arr::unique(['a' => 1, 'b' => 2, 'c' => 2], reindex: true));

        // reindex: false
        self::assertEquals(['a' => 1, 'b' => 2], Arr::unique(['a' => 1, 'b' => 2, 'c' => 2], reindex: false));

        // empty with callback
        /** @var array<int> $array */
        $array = [];
        self::assertEquals([], Arr::unique($array, static fn() => 1));

        // list with callback
        self::assertEquals([1, 2], Arr::unique([1, 2, 3, 4], static fn($v) => $v % 2));

        // assoc with callback
        self::assertEquals(['a' => 1, 'b' => 2], Arr::unique(['a' => 1, 'b' => 2, 'c' => 2], static fn($v) => $v % 2));

        // make sure condition does not convert result to string like array_unique
        self::assertEquals($values, Arr::unique(Arr::repeat($values, 2), static fn($v) => $v));
    }

    public function test_values(): void
    {
        // empty
        self::assertEquals([], Arr::values([]));

        // list
        self::assertEquals([1, 1, 2], Arr::values([1, 1, 2]));

        // assoc
        self::assertEquals([1, 2], Arr::values(['a' => 1, 'b' => 2]));
    }

    public function test_of(): void
    {
        // empty
        self::assertEquals([], Arr::of());

        // list
        self::assertEquals([1, 2, 3], Arr::of(1, 2, 3));

        // assoc
        self::assertEquals(['a' => 1, 'b' => 2], Arr::of(a: 1, b: 2));

        // assoc with int
        self::assertEquals([1, 'a' => 2], Arr::of(1, a: 2));
    }
}
