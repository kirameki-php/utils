<?php declare(strict_types=1);

namespace Tests\Kirameki\Collections;

use Kirameki\Collections\Range;
use function iterator_to_array;

final class RangeTest extends TestCase
{
    public function test_consturct(): void
    {
        $range = new Range(0, 2);
        $this->assertSame([0, 1, 2], iterator_to_array($range), 'include end');

        $range = new Range(0, 2, false);
        $this->assertSame([0, 1], iterator_to_array($range), 'exclude end');
    }

    public function test_all(): void
    {
        $range = new Range(0, 2);
        $this->assertSame([0, 1, 2], $range->all());
    }

    public function test_count(): void
    {
        $range = new Range(0, 2);
        $this->assertSame(2, $range->count());

        $range = new Range(0, 2, false);
        $this->assertSame(1, $range->count());
    }

    public function test_min(): void
    {
        $range = new Range(-2, 2);
        $this->assertSame(-2, $range->min());
    }

    public function test_max(): void
    {
        $range = new Range(-2, 2);
        $this->assertSame(2, $range->max());
    }

    public function test_includesEnd(): void
    {
        $range = new Range(0, 2);
        $this->assertTrue($range->includesEnd());

        $range = new Range(0, 2, false);
        $this->assertFalse($range->includesEnd());
    }
}
