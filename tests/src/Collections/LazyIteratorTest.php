<?php declare(strict_types=1);

namespace Tests\Kirameki\Collections;

use Kirameki\Collections\LazyIterator;
use Kirameki\Exceptions\UnreachableException;
use Traversable;

final class LazyIteratorTest extends TestCase
{
    public function test_constructor_list(): void
    {
        $lazy = new LazyIterator([1, 2, 3]);
        self::assertInstanceOf(LazyIterator::class, $lazy);
    }

    public function test_constructor_map(): void
    {
        $lazy = new LazyIterator(['a' => 1, 'b' => 2]);
        self::assertInstanceOf(LazyIterator::class, $lazy);
    }

    public function test_getIterator(): void
    {
        $lazy = new LazyIterator([1, 2, 3]);
        self::assertInstanceOf(Traversable::class, $lazy->getIterator());
        foreach ($lazy as $index => $value) {
            match ($index) {
                0 => self::assertSame(1, $value),
                1 => self::assertSame(2, $value),
                2 => self::assertSame(3, $value),
                default => throw new UnreachableException(),
            };
        }
    }
}
