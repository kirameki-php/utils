<?php declare(strict_types=1);

namespace Tests\Kirameki\Collections;

use Kirameki\Collections\Exceptions\DuplicateKeyException;
use Kirameki\Collections\Exceptions\EmptyNotAllowedException;
use Kirameki\Collections\Exceptions\InvalidKeyException;
use Kirameki\Collections\Map;
use Kirameki\Collections\MapMutable;
use Kirameki\Core\Exceptions\InvalidArgumentException;

final class MapMutableTest extends TestCase
{
    public function test_constructor(): void
    {
        $map = $this->mapMut(['a' => 1, 'b' => 2]);
        self::assertInstanceOf(MapMutable::class, $map);
        self::assertSame(['a' => 1, 'b' => 2], $map->all());
    }

    public function test_constructor_no_args(): void
    {
        $map = new MapMutable();
        self::assertInstanceOf(MapMutable::class, $map);
        self::assertSame([], $map->all());
    }

    public function test_constructor_empty(): void
    {
        $map = $this->mapMut([]);
        self::assertInstanceOf(MapMutable::class, $map);
        self::assertSame([], $map->all());
    }

    public function test_offsetSet_assignment_with_invalid_key(): void
    {
        $this->expectExceptionMessage("Expected: \$offset's type to be int|string. Got: double.");
        $this->expectException(InvalidArgumentException::class);
        $map = $this->mapMut();
        $map[0.3] = 3;
    }

    public function test_clear(): void
    {
        $map = $this->mapMut([]);
        self::assertSame([], $map->clear()->all(), 'empty map');

        $map = $this->mapMut(['a' => 1, 'b' => 2]);
        self::assertSame([], $map->clear()->all(), 'non-empty map');
    }

    public function test_immutable():void
    {
        self::assertInstanceOf(Map::class, $this->mapMut(['a' => 1])->immutable());
    }

    public function test_insertAt(): void
    {
        self::assertSame(
            ['a' => 1],
            $this->mapMut()->insertAt(0, ['a' => 1])->all(),
            'empty map',
        );

        self::assertSame(
            ['a' => 1],
            $this->mapMut()->insertAt(-100, ['a' => 1])->all(),
            'negative overflows on empty map',
        );

        self::assertSame(
            ['a' => 1],
            $this->mapMut()->insertAt(100, ['a' => 1])->all(),
            'overflows on empty map',
        );

        self::assertSame(
            ['b' => 2, 'a' => 1],
            $this->mapMut(['a' => 1])->insertAt(0, ['b' => 2])->all(),
            'non-empty map',
        );

        self::assertSame(
            ['a' => 1, 'b' => 2, 'c' => 3],
            $this->mapMut(['a' => 1, 'b' => 2])->insertAt(-1, ['c' => 3])->all(),
            'negative insert index',
        );

        self::assertSame(
            message: 'negative insert index',
            actual: $this->mapMut(['a' => 1, 'b' => 2])->insertAt(1, ['c' => 3])->all(),
            expected: ['a' => 1, 'c' => 3, 'b' => 2],
        );

        self::assertSame(
            message: 'insert with overwrite',
            actual: $this->mapMut(['a' => 1, 'b' => 2])->insertAt(-1, ['c' => 3, 'a' => 0], true)->all(),
            expected: ['b' => 2, 'c' => 3, 'a' => 0],
        );
    }

    public function test_insertAt_duplicate_without_overwrite(): void
    {
        $this->expectExceptionMessage('Tried to overwrite existing key: a.');
        $this->expectException(DuplicateKeyException::class);
        $this->mapMut(['a' => 1])->insertAt(0, ['a' => 2]);
    }

    public function test_remove(): void
    {
        $map = $this->mapMut();
        self::assertSame([], $map->remove('a')->all(), 'remove on empty map');

        $map = $this->mapMut(['a' => 1, 'b' => 2, 'c' => 2]);
        self::assertSame(['b', 'c'], $map->remove(2)->all(), 'remove existing value');
        self::assertSame([], $map->remove(2)->all(), 'remove non-existing value');
        self::assertSame(['a' => 1], $map->all(), 'check remains');

        $map = $this->mapMut(['a' => 1, 'b' => 1]);
        self::assertSame(['a'], $map->remove(1, 1)->all(), 'remove only one value');
        self::assertSame(['b' => 1], $map->all(), 'check remains');
    }

    public function test_pop(): void
    {
        $map = $this->mapMut(['a' => 1, 'b' => 2]);
        self::assertSame(2, $map->pop(), 'pop');
        self::assertSame(['a' => 1], $map->all(), 'check remains');
    }

    public function test_pop_on_empty(): void
    {
        $this->expectExceptionMessage('&$array must contain at least one element.');
        $this->expectException(EmptyNotAllowedException::class);
        $this->mapMut()->pop();
    }

    public function test_popMany(): void
    {
        $map = $this->mapMut();
        self::assertSame([], $map->popMany(2)->all(), 'pop empty');

        $map = $this->mapMut(['a' => 1, 'b' => 2]);
        self::assertSame(['b' => 2], $map->popMany(1)->all(), 'pop one');
        self::assertSame(['a' => 1], $map->all(), 'check remains');

        $map = $this->mapMut(['a' => 1, 'b' => 2]);
        self::assertSame(['a' => 1, 'b' => 2], $map->popMany(2)->all(), 'pop to empty');
        self::assertSame([], $map->all(), 'check remains');

        $map = $this->mapMut(['a' => 1, 'b' => 2]);
        self::assertSame(['a' => 1, 'b' => 2], $map->popMany(3)->all(), 'pop overflow');
        self::assertSame([], $map->all(), 'check remains');
    }

    public function test_popMany_zero_amount(): void
    {
        $this->expectExceptionMessage('Expected: $amount >= 1. Got: 0.');
        $this->expectException(InvalidArgumentException::class);
        $this->mapMut()->popMany(0);
    }

    public function test_popMany_negative_amount(): void
    {
        $this->expectExceptionMessage('Expected: $amount >= 1. Got: -1.');
        $this->expectException(InvalidArgumentException::class);
        $this->mapMut()->popMany(-1);
    }

    public function test_popOrNull(): void
    {
        self::assertNull($this->mapMut()->popOrNull(), 'pop on empty');

        $map = $this->mapMut(['a' => 1, 'b' => 2]);
        self::assertSame(2, $map->popOrNull(), 'pop');
        self::assertSame(['a' => 1], $map->all(), 'check remains');
    }

    public function test_pull(): void
    {
        $map = $this->mapMut(['a' => 1, 'b' => 2]);
        self::assertSame(2, $map->pull('b'));
        self::assertSame(['a' => 1], $map->all());
    }

    public function test_pull_on_empty(): void
    {
        $this->expectExceptionMessage('Tried to pull undefined key "a".');
        $this->expectException(InvalidKeyException::class);
        $this->mapMut()->pull('a');
    }

    public function test_pullOr(): void
    {
        $map = $this->mapMut(['a' => 1, 'b' => 2]);
        self::assertSame(2, $map->pullOr('b', 100), 'pull existing');
        self::assertSame(100, $map->pullOr('c', 100), 'pull missing');
        self::assertSame(['a' => 1], $map->all());
    }

    public function test_pullOrNull(): void
    {
        $map = $this->mapMut(['a' => 1, 'b' => 2]);
        self::assertSame(2, $map->pullOrNull('b'));
        self::assertNull($map->pullOrNull('b'));
        self::assertSame(['a' => 1], $map->all());
    }

    public function test_pullMany(): void
    {
        $missed = [];
        self::assertSame([], $this->mapMut()->pullMany(['b'], $missed)->all(), 'pull on empty map');
        self::assertSame(['b'], $missed, 'check missed');

        $map = $this->mapMut(['a' => 1, 'b' => 2, 'c' => 3]);
        self::assertSame(['b' => 2], $map->pullMany(['b'])->all(), 'pull one');
        self::assertSame(['a' => 1, 'c' => 3], $map->all(), 'check remains');

        $map = $this->mapMut(['a' => 1, 'b' => 2, 'c' => 3]);
        self::assertSame(['b' => 2, 'c' => 3], $map->pullMany(['b', 'c'])->all(), 'pull many');
        self::assertSame(['a' => 1], $map->all(), 'check remains');
    }

    public function test_shift(): void
    {
        $map = $this->mapMut(['a' => 1, 'b' => 2]);
        self::assertSame(1, $map->shift(), 'shift');
        self::assertSame(['b' => 2], $map->all(), 'check remains');
    }

    public function test_shift_on_empty(): void
    {
        $this->expectExceptionMessage('&$array must contain at least one element.');
        $this->expectException(EmptyNotAllowedException::class);
        $this->mapMut()->shift();
    }

    public function test_shiftOrNull(): void
    {
        self::assertNull($this->mapMut()->shiftOrNull(), 'shift on empty');

        $map = $this->mapMut(['a' => 1, 'b' => 2]);
        self::assertSame(1, $map->shiftOrNull(), 'shift');
        self::assertSame(['b' => 2], $map->all(), 'check remains');
    }

    public function test_shiftMany(): void
    {
        self::assertSame([], $this->mapMut()->shiftMany(2)->all(), 'shift many on empty');

        $map = $this->mapMut(['a' => 1, 'b' => 2]);
        self::assertSame(['a' => 1], $map->shiftMany(1)->all(), 'shift many one');
        self::assertSame(['b' => 2], $map->all(), 'check remains');

        $map = $this->mapMut(['a' => 1, 'b' => 2]);
        self::assertSame(['a' => 1, 'b' => 2], $map->shiftMany(2)->all(), 'shift many exact');
        self::assertSame([], $map->all(), 'check remains');

        $map = $this->mapMut(['a' => 1, 'b' => 2]);
        self::assertSame(['a' => 1, 'b' => 2], $map->shiftMany(3)->all(), 'shift many amount overflow');
        self::assertSame([], $map->all(), 'check remains');
    }

    public function test_set(): void
    {
        $map = $this->mapMut();
        self::assertSame(['c' => 3], $map->set('c', 3)->all(), 'set on empty');

        $map = $this->mapMut(['a' => 1, 'b' => 2]);
        self::assertSame(['a' => 1, 'b' => 2, 'c' => 3], $map->set('c', 3)->all(), 'set at end');

        $map = $this->mapMut(['a' => 1, 'b' => 0.2]);
        self::assertSame(['a' => 1, 'b' => 0.2, 'c' => 'a'], $map->set('c', 'a')->all(), 'mixed types');
    }

    public function test_setIfExists(): void
    {
        $map = $this->mapMut();
        self::assertSame([], $map->setIfExists('c', 3)->all(), 'set on empty');

        $map = $this->mapMut(['a' => 1]);
        $result = false;
        self::assertSame(['a' => 1], $map->setIfExists('b', 2, $result)->all(), 'key does not exist');
        self::assertFalse($result, 'result');

        $map = $this->mapMut(['a' => 1, 'b' => 2]);
        $result = false;
        self::assertSame(['a' => 1, 'b' => 3], $map->setIfExists('b', 3, $result)->all(), 'key exists');
        self::assertTrue($result, 'result');
    }

    public function test_setIfNotExists(): void
    {
        $map = $this->mapMut();
        self::assertSame(['a' => 1], $map->setIfNotExists('a', 1)->all(), 'set on empty');

        $map = $this->mapMut(['a' => 1]);
        self::assertSame(['a' => 1, 'b' => 2], $map->setIfNotExists('b', 2)->all(), 'key does not exist');

        $map = $this->mapMut(['a' => 1, 'b' => 2]);
        self::assertSame(['a' => 1, 'b' => 2], $map->setIfNotExists('a', 3)->all(), 'key exists');
    }
}
