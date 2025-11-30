<?php declare(strict_types=1);

namespace Tests\Kirameki\Collections\Support;

use Closure;
use DateTime;
use DateTimeImmutable;
use DateTimeInterface;
use IteratorAggregate;
use Kirameki\Collections\Support\TypeChecker;
use Kirameki\Exceptions\InvalidTypeException;
use Kirameki\Testing\TestCase;
use stdClass;
use Tests\Kirameki\Core\_ValueTest\AbstractClass;
use Tests\Kirameki\Core\_ValueTest\ConcreteClass;
use Tests\Kirameki\Core\_ValueTest\IntersectClass;
use Traversable;

final class TypeCheckerTest extends TestCase
{
    public function test_matches(): void
    {
        $this->assertTrue(TypeChecker::check(null, 'null'));
        $this->assertFalse(TypeChecker::check(0, 'null'));
        $this->assertFalse(TypeChecker::check('', 'null'));
        $this->assertFalse(TypeChecker::check('null', 'null'));
        $this->assertFalse(TypeChecker::check([], 'null'));
        $this->assertFalse(TypeChecker::check([null], 'null'));
        $this->assertFalse(TypeChecker::check(new stdClass(), 'null'));
        $this->assertFalse(TypeChecker::check(new class() {}, 'null'));
        $this->assertFalse(TypeChecker::check(fn() => null, 'null'));

        $this->assertTrue(TypeChecker::check(true, 'bool'));
        $this->assertFalse(TypeChecker::check(null, 'bool'));
        $this->assertFalse(TypeChecker::check(0, 'bool'));
        $this->assertFalse(TypeChecker::check('', 'bool'));
        $this->assertFalse(TypeChecker::check('true', 'bool'));
        $this->assertFalse(TypeChecker::check([], 'bool'));
        $this->assertFalse(TypeChecker::check([true], 'int'));
        $this->assertFalse(TypeChecker::check(fn() => true, 'bool'));
        $this->assertFalse(TypeChecker::check(new stdClass(), 'bool'));
        $this->assertFalse(TypeChecker::check(new class() {}, 'bool'));

        $this->assertTrue(TypeChecker::check(1, 'int'));
        $this->assertFalse(TypeChecker::check(null, 'int'));
        $this->assertFalse(TypeChecker::check(1.0, 'int'));
        $this->assertFalse(TypeChecker::check('1', 'int'));
        $this->assertFalse(TypeChecker::check(true, 'int'));
        $this->assertFalse(TypeChecker::check([], 'int'));
        $this->assertFalse(TypeChecker::check([1], 'int'));
        $this->assertFalse(TypeChecker::check(fn() => 1, 'int'));
        $this->assertFalse(TypeChecker::check(new stdClass(), 'int'));
        $this->assertFalse(TypeChecker::check(new class() {}, 'int'));

        $this->assertTrue(TypeChecker::check(1.0, 'float'));
        $this->assertTrue(TypeChecker::check(INF, 'float'));
        $this->assertTrue(TypeChecker::check(NAN, 'float'));
        $this->assertFalse(TypeChecker::check(null, 'float'));
        $this->assertFalse(TypeChecker::check(1, 'float'));
        $this->assertFalse(TypeChecker::check('', 'float'));
        $this->assertFalse(TypeChecker::check('1.0', 'float'));
        $this->assertFalse(TypeChecker::check([], 'float'));
        $this->assertFalse(TypeChecker::check([1], 'float'));
        $this->assertFalse(TypeChecker::check(fn() => 1.0, 'float'));
        $this->assertFalse(TypeChecker::check(new stdClass(), 'float'));
        $this->assertFalse(TypeChecker::check(new class() {}, 'float'));

        $this->assertTrue(TypeChecker::check('1', 'string'));
        $this->assertTrue(TypeChecker::check('', 'string'));
        $this->assertTrue(TypeChecker::check(DateTime::class, 'string'));
        $this->assertFalse(TypeChecker::check(null, 'string'));
        $this->assertFalse(TypeChecker::check(1, 'string'));
        $this->assertFalse(TypeChecker::check(1.0, 'string'));
        $this->assertFalse(TypeChecker::check(false, 'string'));
        $this->assertFalse(TypeChecker::check([], 'string'));
        $this->assertFalse(TypeChecker::check([1], 'string'));
        $this->assertFalse(TypeChecker::check(fn() => '', 'string'));
        $this->assertFalse(TypeChecker::check(new stdClass(), 'string'));
        $this->assertFalse(TypeChecker::check(new class() {}, 'string'));

        $this->assertTrue(TypeChecker::check([], 'array'));
        $this->assertTrue(TypeChecker::check([1], 'array'));
        $this->assertTrue(TypeChecker::check(['a' => 1], 'array'));
        $this->assertFalse(TypeChecker::check('[]', 'array'));
        $this->assertFalse(TypeChecker::check('', 'array'));
        $this->assertFalse(TypeChecker::check(null, 'array'));
        $this->assertFalse(TypeChecker::check(1, 'array'));
        $this->assertFalse(TypeChecker::check(false, 'array'));
        $this->assertFalse(TypeChecker::check(new stdClass(), 'array'));
        $this->assertFalse(TypeChecker::check(new class() {}, 'array'));

        $this->assertTrue(TypeChecker::check(new stdClass(), 'object'));
        $this->assertTrue(TypeChecker::check(new class() {}, 'object'));
        $this->assertFalse(TypeChecker::check(DateTime::class, 'object'));
        $this->assertFalse(TypeChecker::check(null, 'object'));
        $this->assertFalse(TypeChecker::check(1, 'object'));
        $this->assertFalse(TypeChecker::check(1.0, 'object'));
        $this->assertFalse(TypeChecker::check(false, 'object'));
        $this->assertFalse(TypeChecker::check('', 'object'));
        $this->assertFalse(TypeChecker::check('object', 'object'));
        $this->assertFalse(TypeChecker::check([], 'object'));
        $this->assertFalse(TypeChecker::check([1], 'object'));
        $this->assertFalse(TypeChecker::check(fn() => new stdClass(), 'scalar'));

        $this->assertTrue(TypeChecker::check([], 'iterable'));
        $this->assertTrue(TypeChecker::check([1], 'iterable'));
        $this->assertTrue(TypeChecker::check(['a' => 1], 'iterable'));
        $this->assertTrue(TypeChecker::check(new class() implements IteratorAggregate { public function getIterator(): Traversable { yield 1; } }, 'iterable'));
        $this->assertFalse(TypeChecker::check('[]', 'iterable'));
        $this->assertFalse(TypeChecker::check('', 'iterable'));
        $this->assertFalse(TypeChecker::check(null, 'iterable'));
        $this->assertFalse(TypeChecker::check(1, 'iterable'));
        $this->assertFalse(TypeChecker::check(false, 'iterable'));
        $this->assertFalse(TypeChecker::check(new stdClass(), 'iterable'));
        $this->assertFalse(TypeChecker::check(fn() => [], 'iterable'));

        $this->assertTrue(TypeChecker::check('strlen', 'callable'));
        $this->assertTrue(TypeChecker::check(strlen(...), 'callable'));
        $this->assertTrue(TypeChecker::check(fn() => true, 'callable'));
        $this->assertTrue(TypeChecker::check([$this, 'test_matches'], 'callable'));
        $this->assertFalse(TypeChecker::check(null, 'callable'));
        $this->assertFalse(TypeChecker::check(1, 'callable'));
        $this->assertFalse(TypeChecker::check(false, 'callable'));
        $this->assertFalse(TypeChecker::check('', 'callable'));
        $this->assertFalse(TypeChecker::check('?', 'callable'));
        $this->assertFalse(TypeChecker::check([], 'callable'));
        $this->assertFalse(TypeChecker::check([1], 'callable'));
        $this->assertFalse(TypeChecker::check(new stdClass(), 'callable'));

        $this->assertTrue(TypeChecker::check(1, 'scalar'));
        $this->assertTrue(TypeChecker::check(1.0, 'scalar'));
        $this->assertTrue(TypeChecker::check(false, 'scalar'));
        $this->assertTrue(TypeChecker::check('', 'scalar'));
        $this->assertTrue(TypeChecker::check('?', 'scalar'));
        $this->assertTrue(TypeChecker::check(DateTime::class, 'scalar'));
        $this->assertFalse(TypeChecker::check(null, 'scalar'));
        $this->assertFalse(TypeChecker::check([], 'scalar'));
        $this->assertFalse(TypeChecker::check([1], 'scalar'));
        $this->assertFalse(TypeChecker::check(new stdClass(), 'scalar'));
        $this->assertFalse(TypeChecker::check(new class() {}, 'scalar'));
        $this->assertFalse(TypeChecker::check(fn() => true, 'scalar'));

        // open resource
        $resource = fopen(__FILE__, 'r');
        $this->assertTrue(TypeChecker::check($resource, 'resource'));
        if(is_resource($resource)) fclose($resource);
        // closed resource
        $resource = fopen(__FILE__, 'r');
        if(is_resource($resource)) fclose($resource);
        $this->assertTrue(TypeChecker::check($resource, 'resource'));
        $this->assertFalse(TypeChecker::check(null, 'resource'));
        $this->assertFalse(TypeChecker::check(1, 'resource'));
        $this->assertFalse(TypeChecker::check(false, 'resource'));
        $this->assertFalse(TypeChecker::check('', 'resource'));
        $this->assertFalse(TypeChecker::check([], 'resource'));
        $this->assertFalse(TypeChecker::check(new stdClass(), 'resource'));
        $this->assertFalse(TypeChecker::check(fn() => true, 'resource'));

        $this->assertTrue(TypeChecker::check(1, 'mixed'));
        $this->assertTrue(TypeChecker::check(1.0, 'mixed'));
        $this->assertTrue(TypeChecker::check(false, 'mixed'));
        $this->assertTrue(TypeChecker::check('', 'mixed'));
        $this->assertTrue(TypeChecker::check('?', 'mixed'));
        $this->assertTrue(TypeChecker::check(DateTime::class, 'mixed'));
        $this->assertTrue(TypeChecker::check(null, 'mixed'));
        $this->assertTrue(TypeChecker::check([], 'mixed'));
        $this->assertTrue(TypeChecker::check([1], 'mixed'));
        $this->assertTrue(TypeChecker::check(new stdClass(), 'mixed'));
        $this->assertTrue(TypeChecker::check(new class() {}, 'mixed'));
        $this->assertTrue(TypeChecker::check(fn() => true, 'mixed'));

        $this->assertTrue(TypeChecker::check(new DateTime(), DateTimeInterface::class));
        $this->assertTrue(TypeChecker::check(new DateTimeImmutable(), DateTimeInterface::class));
        $this->assertTrue(TypeChecker::check(new ConcreteClass(), AbstractClass::class));
        $this->assertTrue(TypeChecker::check(fn() => true, Closure::class));
        $this->assertFalse(TypeChecker::check(1, DateTimeInterface::class));
        $this->assertFalse(TypeChecker::check(false, DateTimeInterface::class));
        $this->assertFalse(TypeChecker::check('', DateTimeInterface::class));
        $this->assertFalse(TypeChecker::check([], DateTimeInterface::class));
        $this->assertFalse(TypeChecker::check(new stdClass(), DateTimeInterface::class));
        $this->assertFalse(TypeChecker::check(fn() => true, DateTimeInterface::class));

        // union types
        $this->assertTrue(TypeChecker::check(1, 'int|null'));
        $this->assertTrue(TypeChecker::check(null, 'int|null'));
        $this->assertTrue(TypeChecker::check(1, 'int|float'));
        $this->assertTrue(TypeChecker::check(1.0, 'int|float'));
        $this->assertTrue(TypeChecker::check('1', 'int|float|string'));
        $this->assertTrue(TypeChecker::check([], 'array|object'));
        $this->assertTrue(TypeChecker::check(new stdClass(), 'array|' . stdClass::class));
        $this->assertFalse(TypeChecker::check(false, 'int|null'));
        $this->assertFalse(TypeChecker::check('', 'int|float'));

        // intersection types
        $this->assertTrue(TypeChecker::check(new IntersectClass(), 'Stringable&Countable'));
        $this->assertFalse(TypeChecker::check(1, 'int&null'));

        // mixed types
        $this->assertTrue(TypeChecker::check(1, 'int|(Stringable&Countable)'));
        $this->assertTrue(TypeChecker::check(new IntersectClass(), 'int|(Stringable&Countable)'));
        $this->assertFalse(TypeChecker::check(null, 'int|(Stringable&Countable)'));
        $this->assertFalse(TypeChecker::check(1.0, 'int|(Stringable&Countable)'));
        $this->assertFalse(TypeChecker::check('', 'int|(Stringable&Countable)'));

        // invalid type but passes since it never gets there.
        $this->assertTrue(TypeChecker::check(1, 'int|Stringable&Countable'));
    }

    public function test_of_with_invalid_type(): void
    {
        $this->expectExceptionMessage('Invalid type: hi');
        $this->expectException(InvalidTypeException::class);
        TypeChecker::check(1, 'hi|none');
    }

    public function test_of_with_mixed_type_without_parentheses(): void
    {
        $this->expectExceptionMessage('Invalid Type: Stringable&Countable|int (Intersection type missing parentheses?)');
        $this->expectException(InvalidTypeException::class);
        TypeChecker::check(1, 'Stringable&Countable|int');
    }
}
