<?php declare(strict_types=1);

namespace Tests\Kirameki\Collections;

use Kirameki\Collections\Exceptions\IndexOutOfBoundsException;
use Kirameki\Collections\Exceptions\InvalidKeyException;
use Kirameki\Collections\Vec;
use Kirameki\Core\Exceptions\TypeMismatchException;

final class VecMutableTest extends TestCase
{
    public function test_constructor(): void
    {
        $vec = $this->vecMut([1, 2]);
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
        $vec = $this->vecMut([]);
        self::assertInstanceOf(Vec::class, $vec);
        self::assertSame([], $vec->all());
    }

    public function test_constructor_non_list(): void
    {
        $this->expectExceptionMessage('$items must be a list, map given.');
        $this->expectException(TypeMismatchException::class);
        $this->vecMut(['a' => 1]);
    }

    public function test_offsetSet(): void
    {
        $vec = $this->vecMut([1, 2]);
        $vec[0] = 3;
        self::assertSame([3, 2], $vec->all(), 'Overwriting existing value');

        $vec = $this->vecMut([1, 2]);
        $vec[] = 3;
        self::assertSame([1, 2, 3], $vec->all(), 'Appending to the end');

        $vec = $this->vecMut([1, 2]);
        $vec->offsetSet(0, 3);
        self::assertSame([3, 2], $vec->all(), 'Overwriting existing value using method');

        $vec = $this->vecMut([1, 2]);
        $vec->offsetSet(null, 3);
        self::assertSame([1, 2, 3], $vec->all(), 'Appending to the end using method');
    }

    public function test_offsetSet_non_int_access(): void
    {
        $this->expectExceptionMessage('Expected: $offset\'s type to be int|null. Got: string.');
        $this->expectException(InvalidKeyException::class);
        $this->vecMut([1, 2])['0'] = 3;
    }

    public function test_offsetSet_assignment_out_of_bounds(): void
    {
        $this->expectExceptionMessage('Can not assign to a non-existing index. (size: 2 index: 3)');
        $this->expectException(IndexOutOfBoundsException::class);
        $vec = $this->vecMut([1, 2]);
        $vec[3] = 3;
    }

    public function test_offsetUnset(): void
    {
        $vec = $this->vecMut([1, 2]);
        unset($vec[0]);
        self::assertSame([2], $vec->all(), 'Unset first element');

        $vec = $this->vecMut([1, 2, 3]);
        unset($vec[1]);
        self::assertSame([1, 3], $vec->all(), 'Unset middle element');

        $vec = $this->vecMut([1, 2]);
        unset($vec[2]);
        self::assertSame([1, 2], $vec->all(), 'Unset non-existing element');

        $vec = $this->vecMut([1, 2]);
        $vec->offsetUnset(0);
        self::assertSame([2], $vec->all(), 'Unset first element using method');

    }

    public function test_offsetUnset_non_int_access(): void
    {
        $this->expectExceptionMessage('Expected: $offset\'s type to be int|null. Got: string.');
        $this->expectException(InvalidKeyException::class);
        unset($this->vecMut([1, 2])['0']);
    }

    public function test_immutable():void
    {
        self::assertInstanceOf(Vec::class, $this->vecMut([1])->immutable());
    }
}
