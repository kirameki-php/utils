<?php declare(strict_types=1);

namespace Tests\Kirameki\Collections;

use Kirameki\Collections\Exceptions\EmptyNotAllowedException;
use Kirameki\Collections\Exceptions\InvalidKeyException;
use Kirameki\Collections\Exceptions\MissingKeyException;
use Kirameki\Collections\LazyIterator;
use Kirameki\Collections\Vec;
use Kirameki\Collections\VecMutable;
use Kirameki\Core\Exceptions\InvalidArgumentException;
use Kirameki\Core\Exceptions\NotSupportedException;
use Kirameki\Core\Exceptions\TypeMismatchException;
use Random\Engine\Xoshiro256StarStar;
use Random\Randomizer;

final class VecTest extends TestCase
{
    public function test_constructor(): void
    {
        $vec = $this->vec([1, 2]);
        self::assertInstanceOf(Vec::class, $vec);
        self::assertSame([1, 2], $vec->all());
    }

    public function test_constructor_no_args(): void
    {
        $vec = new Vec();
        self::assertInstanceOf(Vec::class, $vec);
        self::assertSame([], $vec->all());
    }

    public function test_constructor_empty(): void
    {
        $vec = $this->vec([]);
        self::assertInstanceOf(Vec::class, $vec);
        self::assertSame([], $vec->all());
    }

    public function test_constructor_non_list(): void
    {
        $this->expectExceptionMessage('$items must be a list, map given.');
        $this->expectException(TypeMismatchException::class);
        $this->vec(['a' => 1]);
    }

    public function test_indices(): void
    {
        $this->assertInstanceOf(Vec::class, $this->vec()->indices(), 'instance');
        $this->assertSame([], $this->vec()->indices()->all(), 'empty');
        $this->assertSame([0, 1], $this->vec([1, 2])->indices()->all(), 'indices');
    }

    public function test_loop(): void
    {
        $vec = Vec::loop(3);
        self::assertSame([0, 1, 2], $vec->all());
    }

    public function test_range(): void
    {
        self::assertSame([1], Vec::range(1, 1)->all());
        self::assertSame([1, 2, 3], Vec::range(1, 3)->all());
        self::assertSame([-2, -1, 0], Vec::range(-2, 0)->all());
        self::assertSame([1], Vec::range(1, 2, false)->all());
        self::assertSame([0, 1], Vec::range(0, 2, false)->all());
    }

    public function test_range_with_smaller_end_positive(): void
    {
        $this->expectExceptionMessage('$start must be <= $end. Got: 1 -> 0');
        $this->expectException(InvalidArgumentException::class);
        Vec::range(1, 0);
    }

    public function test_range_with_smaller_end_negative(): void
    {
        $this->expectExceptionMessage('$start must be <= $end. Got: -1 -> -2');
        $this->expectException(InvalidArgumentException::class);
        Vec::range(-1, -2);
    }

    public function test_range_with_same_num(): void
    {
        $this->expectExceptionMessage('$start must be < $end when end is not included. Got: 1 -> 1');
        $this->expectException(InvalidArgumentException::class);
        Vec::range(1, 1, false);
    }

    public function test_jsonSerialize(): void
    {
        self::assertSame([1, 2], $this->vec([1, 2])->jsonSerialize());
    }

    public function test_offsetExists(): void
    {
        self::assertTrue(isset($this->vec([1, 2])[0]));
        self::assertFalse(isset($this->vec([1, 2])[2]));

        self::assertTrue($this->vec([1, 2])->offsetExists(0));
        self::assertFalse($this->vec([1, 2])->offsetExists(2));
    }

    public function test_offsetExists_non_int_access(): void
    {
        $this->expectExceptionMessage('Expected: $offset\'s type to be int|null. Got: string.');
        $this->expectException(InvalidKeyException::class);
        isset($this->vec([1, 2])['0']);
    }

    public function test_offsetGet(): void
    {
        self::assertSame(1, $this->vec([1, 2])[0]);
        self::assertSame('a', $this->vec([1, 2])[2] ?? 'a', 'Null coalescing');

        self::assertSame(1, $this->vec([1, 2])->offsetGet(0));
    }

    public function test_offsetGet_invalid_type(): void
    {
        $this->throwOnError();
        $this->expectExceptionMessage("Vec's inner item must be of type array|ArrayAccess, Kirameki\Collections\LazyIterator given.");
        $this->expectException(NotSupportedException::class);
        $vec = $this->vec(new LazyIterator([1]));
        $vec[0];
    }

    public function test_offsetGet_non_int_access(): void
    {
        $this->expectExceptionMessage('Expected: $offset\'s type to be int|null. Got: string.');
        $this->expectException(InvalidKeyException::class);
        $this->vec([1, 2])['0'];
    }

    public function test_offsetSet_throws_error(): void
    {
        $this->expectExceptionMessage('Kirameki\Collections\Vec::offsetSet is not supported.');
        $this->expectException(NotSupportedException::class);
        $this->vec([1, 2])['0'] = 3;
    }

    public function test_offsetUnset(): void
    {
        $this->expectExceptionMessage('Kirameki\Collections\Vec::offsetUnset is not supported.');
        $this->expectException(NotSupportedException::class);
        $vec = $this->vec([1, 2]);
        unset($vec[0]);
    }

    public function test_append(): void
    {
        self::assertSame([3], $this->vec()->append(3)->all(), 'on empty');
        self::assertSame([1], $this->vec([1])->append()->all(), 'append nothing');
        self::assertSame([1, 2, 3], $this->vec([1, 2])->append(3)->all());
        self::assertSame([1, 2, 3, 3, 4], $this->vec([1, 2])->append(3, 3, 4)->all(), 'append multiple');
        self::assertSame([1, 2, 3], $this->vec([1, 2])->append(a: 3)->all(), 'named args');
    }

    public function test_dropIndices(): void
    {
        $this->assertSame([], $this->vec()->dropIndices([])->all(), 'empty');
        $this->assertSame([1], $this->vec([1])->dropIndices([])->all(), 'remove none');
        $this->assertSame([1], $this->vec([1, 2])->dropIndices([1])->all(), 'remove one');
        $this->assertSame([], $this->vec([1, 2])->dropIndices([0, 1])->all(), 'remove all');
        $this->assertSame(['a'], $this->vec(['a'])->dropIndices([1], false)->all(), 'remove missing unsafe');
    }

    public function test_dropIndices_safe_vec(): void
    {
        $this->expectExceptionMessage('Keys: [0, 1] did not exist.');
        $this->expectException(MissingKeyException::class);
        $this->vec()->dropIndices([0, 1])->all();
    }

    public function test_map():void
    {
        $mapped = $this->vec([1, 2])->map(fn($v): int => $v * 2);
        self::assertSame([2, 4], $mapped->all());
    }

    public function test_mutable():void
    {
        self::assertInstanceOf(VecMutable::class, $this->vec([1])->mutable());
        self::assertInstanceOf(VecMutable::class, $this->vec([1])->lazy()->mutable());
    }

    public function test_padLeft(): void
    {
        self::assertSame([], $this->vec()->padLeft(0, 1)->all(), 'zero pad');
        self::assertSame([1, 1], $this->vec()->padLeft(2, 1)->all(), 'on empty');
        self::assertSame([0], $this->vec([0])->padLeft(1, 1)->all(), 'no pad');
        self::assertSame([0], $this->vec([0])->padLeft(1, 1)->all(), 'pad');
        self::assertSame([1, 1, 0], $this->vec([0])->padLeft(3, 1)->all(), 'pad');
    }

    public function test_padLeft_with_negative_length(): void
    {
        $this->expectExceptionMessage('Expected: $length >= 0. Got: -1');
        $this->expectException(InvalidArgumentException::class);
        $this->vec([0])->padLeft(-1, 2);
    }

    public function test_padRight(): void
    {
        self::assertSame([], $this->vec()->padRight(0, 1)->all(), 'zero pad');
        self::assertSame([1, 1], $this->vec()->padRight(2, 1)->all(), 'on empty');
        self::assertSame([0], $this->vec([0])->padRight(1, 1)->all(), 'no pad');
        self::assertSame([0, 1, 1], $this->vec([0])->padRight(3, 1)->all(), 'pad');
    }

    public function test_padRight_with_negative_length(): void
    {
        $this->expectExceptionMessage('Expected: $length >= 0. Got: -1');
        $this->expectException(InvalidArgumentException::class);
        $this->vec([0])->padRight(-1, 2);
    }

    public function test_prepend(): void
    {
        self::assertSame([], $this->vec()->prepend()->all(), 'empty prepend on empty array');
        self::assertSame([1], $this->vec()->prepend(1)->all(), 'prepend on empty array');
        self::assertSame([1], $this->vec([1])->prepend()->all(), 'empty prepend');
        self::assertSame([1, 2], $this->vec([2])->prepend(1)->all(), 'prepend one');
        self::assertSame([1, 1, 2], $this->vec([2])->prepend(1, 1)->all(), 'prepend multi');
        self::assertSame([1, 2], $this->vec([2])->prepend(a: 1)->all(), 'named args');
    }

    public function test_repeat(): void
    {
        self::assertSame([], $this->vec()->repeat(2)->all(), 'empty repeat');
        self::assertSame([1], $this->vec([1])->repeat(1)->all(), 'repeat single');
        self::assertSame([1, 2], $this->vec([1, 2])->repeat(1)->all(), 'repeat multiple');
        self::assertSame([1, 1], $this->vec([1])->repeat(2)->all(), 'repeat x2');
        self::assertSame([1, 2, 1, 2], $this->vec([1, 2])->repeat(2)->all(), 'repeat multi x2');
    }

    public function test_repeat_negative(): void
    {
        $this->expectExceptionMessage('Expected: $times >= 0. Got: -1.');
        $this->expectException(InvalidArgumentException::class);
        $this->vec([1])->repeat(-1);
    }

    public function test_reindex(): void
    {
        self::assertSame([1, 3], $this->vec([1, 2, 3])->dropIndices([1])->all(), 'with except');
        self::assertSame([1, 3], $this->vec([1, 2, 3])->takeIf(fn($n) => (bool)($n % 2))->all(), 'with filter');
        self::assertSame([2], $this->vec([1, 2, 3])->takeIndices([1])->all(), 'with only');
        self::assertSame([2, 1], $this->vec([1, 2])->reverse()->all(), 'with reverse');
        self::assertSame([1, 2], $this->vec([null, 1, 2])->without(null)->all(), 'with without');
    }

    public function test_sampleIndex(): void
    {
        self::assertLessThanOrEqual(2, $this->vec([1, 2, 3])->sampleIndex(), 'sample index');

        $randomizer = new Randomizer(new Xoshiro256StarStar(seed: 1));
        $index = $this->vec([10, 20, 30])->sampleIndex($randomizer);
        self::assertSame(1, $index, 'sample index with randomizer');
    }

    public function test_sampleIndex_on_empty(): void
    {
        $this->expectExceptionMessage('$iterable must contain at least one element.');
        $this->expectException(EmptyNotAllowedException::class);
        $this->vec()->sampleIndex();
    }

    public function test_sampleIndexOrNull(): void
    {
        self::assertLessThanOrEqual(2, $this->vec([1, 2, 3])->sampleIndexOrNull(), 'sample index');

        self::assertNull($this->vec()->sampleIndexOrNull(), 'sample empty array');

        $randomizer = new Randomizer(new Xoshiro256StarStar(seed: 1));
        $index = $this->vec([10, 20, 30])->sampleIndexOrNull($randomizer);
        self::assertSame(1, $index, 'sample index with randomizer');
    }

    public function test_sampleIndexes(): void
    {
        self::assertSame([], $this->vec([1])->sampleIndexes(0)->all(), 'sample zero');
        self::assertSame([0], $this->vec([1])->sampleIndexes(1)->all(), 'sample one');
        self::assertSame([0, 0], $this->vec([1])->sampleIndexes(2, true)->all(), 'sample overflow with replacement');

        $randomizer = new Randomizer(new Xoshiro256StarStar(seed: 1));
        self::assertSame([0, 1], $this->vec([10, 20])->sampleIndexes(2, false, $randomizer)->all(), 'size == amount');
        self::assertSame([2, 1], $this->vec([10, 20, 30])->sampleIndexes(2, false, $randomizer)->all(), 'size > amount');
        self::assertSame([1, 1], $this->vec([10, 20])->sampleIndexes(2, true, $randomizer)->all(), 'size == amount with replacement');
        self::assertSame([0, 0], $this->vec([10, 20])->sampleIndexes(2, true, $randomizer)->all(), 'size > amount with replacement');
        self::assertSame([1, 1, 0], $this->vec([10, 20])->sampleIndexes(3, true, $randomizer)->all(), 'size < amount with replacement');
    }

    public function test_sampleIndexes_overflow(): void
    {
        $this->expectExceptionMessage('$amount must be between 0 and size of $iterable.');
        $this->expectException(InvalidArgumentException::class);
        $this->vec([1])->sampleIndexes(2);
    }

    public function test_symDiff(): void
    {
        self::assertSame([1], $this->vec()->symDiff([1])->toArray(), 'empty array1');
        self::assertSame([1], $this->vec([1])->symDiff([])->toArray(), 'empty array2');
        self::assertSame([], $this->vec([1, 2])->symDiff([2, 1])->toArray(), 'same but not same order');
        self::assertSame([1, 3], $this->vec([1, 2])->symDiff([2, 3])->toArray(), 'basic usage');
        self::assertSame(
            [[1], [3]],
            $this->vec([[1], [2]])->symDiff([[2], [3]], fn($a, $b) => $a[0] <=> $b[0])->toArray(),
            'use by callback',
        );
    }

    public function test_symDiff_cannot_use_map(): void
    {
        $this->expectExceptionMessage('$iterable2 must be a list, map given.');
        $this->expectException(TypeMismatchException::class);
        $this->vec([1])->symDiff(['a' => 1]);
    }

    public function test_swap(): void
    {
        $this->assertSame([1, 3, 2, 4], $this->vec([1, 2, 3, 4])->swap(1, 2)->all(), 'swap list');
    }

    public function test_swap_non_existing_key1(): void
    {
        $this->expectExceptionMessage('Key: 0 does not exist.');
        $this->expectException(InvalidKeyException::class);
        $this->vec()->swap(0, 1);
    }

    public function test_swap_non_existing_key2(): void
    {
        $this->expectExceptionMessage('Key: 1 does not exist.');
        $this->expectException(InvalidKeyException::class);
        $this->vec([1])->swap(0, 1);
    }

    public function test_takeIndices(): void
    {
        $this->assertSame([], $this->vec()->takeIndices([])->all(), 'empty');
        $this->assertSame([], $this->vec([1])->takeIndices([])->all(), 'take none');
        $this->assertSame([2], $this->vec([1, 2])->takeIndices([1])->all(), 'take one');
        $this->assertSame([1, 2], $this->vec([1, 2])->takeIndices([0, 1])->all(), 'take all');
        $this->assertSame([], $this->vec(['a'])->takeIndices([1], false)->all(), 'take missing unsafe');
    }

    public function test_takeIndices_safe_vec(): void
    {
        $this->expectExceptionMessage('Keys: [0, 1] did not exist.');
        $this->expectException(MissingKeyException::class);
        $this->vec()->takeIndices([0, 1])->all();
    }

    public function test_zip(): void
    {
        self::assertSame([], $this->vec()->zip([])->toArray(), 'empty list');
        self::assertSame([[1, null, 5], [2, null, null]], $this->vec([1, 2])->zip([], [5])->toArray(), 'list uneven');
        self::assertSame([[1, 3, 5], [2, 4, 6]], $this->vec([1, 2])->zip([3, 4], [5, 6])->toArray(), 'list even');
    }

    public function test_zip_no_arg(): void
    {
        $this->expectExceptionMessage('Expected: at least 1 argument. Got: 0.');
        $this->expectException(InvalidArgumentException::class);
        $this->vec([1])->zip();
    }

    public function test_zip_map_arg(): void
    {
        $this->expectExceptionMessage('Argument #2 must be a list, map given.');
        $this->expectException(TypeMismatchException::class);
        $this->vec([1])->zip(['a' => 1]);
    }
}
