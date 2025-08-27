<?php declare(strict_types=1);

namespace Tests\Kirameki\Core;

use Kirameki\Core\Lazy;
use Kirameki\Testing\TestCase;

final class LazyTest extends TestCase
{
    public function test_constructor(): void
    {
        $resolver = fn() => 'test value';
        $lazy = new Lazy($resolver);

        $this->assertFalse($lazy->resolved);
    }

    public function test_value_resolved_on_first_access(): void
    {
        $callCount = 0;
        $resolver = function() use (&$callCount) {
            $callCount++;
            return 'computed value';
        };

        $lazy = new Lazy($resolver);

        // Before accessing value
        $this->assertFalse($lazy->resolved);
        $this->assertSame(0, $callCount);

        // First access
        $value = $lazy->value;
        $this->assertSame('computed value', $value);
        $this->assertTrue($lazy->resolved);
        $this->assertSame(1, $callCount);
    }

    public function test_value_not_resolved_multiple_times(): void
    {
        $callCount = 0;
        $resolver = function() use (&$callCount) {
            $callCount++;
            return 'computed value';
        };

        $lazy = new Lazy($resolver);

        // Multiple accesses
        $value1 = $lazy->value;
        $value2 = $lazy->value;
        $value3 = $lazy->value;

        $this->assertSame('computed value', $value1);
        $this->assertSame('computed value', $value2);
        $this->assertSame('computed value', $value3);
        $this->assertTrue($lazy->resolved);
        $this->assertSame(1, $callCount); // Resolver called only once
    }

    public function test_with_different_value_types(): void
    {
        // Integer
        $intLazy = new Lazy(fn() => 42);
        $this->assertSame(42, $intLazy->value);
        $this->assertTrue($intLazy->resolved);

        // Array
        $arrayLazy = new Lazy(fn() => [1, 2, 3]);
        $this->assertSame([1, 2, 3], $arrayLazy->value);
        $this->assertTrue($arrayLazy->resolved);

        // Object
        $object = new \stdClass();
        $object->prop = 'test';
        $objectLazy = new Lazy(fn() => $object);
        $this->assertSame($object, $objectLazy->value);
        $this->assertTrue($objectLazy->resolved);

        // Null
        $nullLazy = new Lazy(fn() => null);
        $this->assertNull($nullLazy->value);
        $this->assertTrue($nullLazy->resolved);
    }

    public function test_exception_in_resolver(): void
    {
        $resolver = function() {
            throw new \RuntimeException('Computation failed');
        };

        $lazy = new Lazy($resolver);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Computation failed');

        // Exception should be thrown when accessing value
        $lazy->value;
    }

    public function test_exception_in_resolver_does_not_mark_as_resolved(): void
    {
        $callCount = 0;
        $resolver = function() use (&$callCount) {
            $callCount++;
            throw new \RuntimeException('Computation failed');
        };

        $lazy = new Lazy($resolver);

        // First attempt
        try {
            $lazy->value;
        } catch (\RuntimeException) {
            // Expected
        }

        $this->assertFalse($lazy->resolved);
        $this->assertSame(1, $callCount);

        // Second attempt should call resolver again
        try {
            $lazy->value;
        } catch (\RuntimeException) {
            // Expected
        }

        $this->assertFalse($lazy->resolved);
        $this->assertSame(2, $callCount);
    }

    public function test_with_boolean_values(): void
    {
        $trueLazy = new Lazy(fn() => true);
        $falseLazy = new Lazy(fn() => false);

        $this->assertTrue($trueLazy->value);
        $this->assertFalse($falseLazy->value);
        $this->assertTrue($trueLazy->resolved);
        $this->assertTrue($falseLazy->resolved);
    }

    public function test_with_empty_values(): void
    {
        $emptyStringLazy = new Lazy(fn() => '');
        $emptyArrayLazy = new Lazy(fn() => []);
        $zeroLazy = new Lazy(fn() => 0);

        $this->assertSame('', $emptyStringLazy->value);
        $this->assertSame([], $emptyArrayLazy->value);
        $this->assertSame(0, $zeroLazy->value);

        $this->assertTrue($emptyStringLazy->resolved);
        $this->assertTrue($emptyArrayLazy->resolved);
        $this->assertTrue($zeroLazy->resolved);
    }
}
