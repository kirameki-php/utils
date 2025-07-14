<?php declare(strict_types=1);

namespace Tests\Kirameki\Collections;

use Kirameki\Collections\Enumerator;
use Kirameki\Collections\LazyIterator;

final class EnumeratorTest extends TestCase
{
    /**
     * @template TValue
     * @param iterable<int, TValue> $items
     * @return Enumerator<int, TValue>
     */
    private function enumerator(iterable $items = []): Enumerator
    {
        return new class($items) extends Enumerator {
            protected function reindex(): bool { return false; }

            /**
             * @inheritDoc
             */
            public function instantiate(mixed $iterable): static
            {
                return new static($iterable);
            }
        };
    }

    public function test_eager(): void
    {
        $eager = $this->enumerator([1, 2]);
        self::assertInstanceOf(Enumerator::class, $eager);
        self::assertTrue($eager->isEager());
        self::assertFalse($eager->isLazy());

        $eager = $this->enumerator([1, 2])->lazy()->eager();
        self::assertInstanceOf(Enumerator::class, $eager);
        self::assertTrue($eager->isEager());
        self::assertFalse($eager->isLazy());
    }

    public function test_instantiate(): void
    {
        $enumerator = $this->enumerator([1, 2])->instantiate([3]);
        self::assertInstanceOf(Enumerator::class, $enumerator);
        self::assertSame([3], $enumerator->all());

        $lazy = $this->enumerator([1, 2])->instantiate(new LazyIterator([3]));
        self::assertTrue($lazy->isLazy());
        self::assertFalse($lazy->isEager());
        self::assertSame([3], $enumerator->all());
    }

    public function test_isEager(): void
    {
        $lazy = $this->enumerator([1, 2]);
        self::assertTrue($lazy->isEager());
    }

    public function test_isLazy(): void
    {
        $lazy = $this->enumerator([1, 2])->lazy();
        self::assertTrue($lazy->isLazy());
    }

    public function test_lazy(): void
    {
        $lazy = $this->enumerator([1, 2])->lazy();
        self::assertInstanceOf(Enumerator::class, $lazy);
        self::assertTrue($lazy->isLazy());
        self::assertFalse($lazy->isEager());
    }

    public function test_tap(): void
    {
        $count = 0;
        $tapped = $this->enumerator([1, 2])->tap(function(Enumerator $e) use (&$count) {
            $count++;
        });
        self::assertSame(1, $count);
        self::assertInstanceOf(Enumerator::class, $tapped);
        self::assertSame([1, 2], $tapped->all());
    }
}
