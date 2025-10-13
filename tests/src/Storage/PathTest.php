<?php

declare(strict_types=1);

namespace Tests\Kirameki\Storage;

use Kirameki\Storage\Directory;
use Kirameki\Storage\Path;
use PHPUnit\Framework\TestCase;

class PathTest extends TestCase
{
    public function test_combine_basic(): void
    {
        $this->assertSame('a/b/c', Path::combine('a', 'b', 'c'));
        $this->assertSame('a/b/c', Path::combine('a/', '/b/', 'c'));
        $this->assertSame('a/b', Path::combine('a', '', 'b'));
        $this->assertSame('', Path::combine('', ''));
        $this->assertSame('/a/b', Path::combine('/a', 'b'));
        $this->assertSame('/', Path::combine('/'));
        $this->assertSame('/', Path::combine('/', '/'));
    }

    public function test_isAbsolute_and_isRelative(): void
    {
        $this->assertTrue(Path::of('/a/b')->isAbsolute());
        $this->assertFalse(Path::of('a/b')->isAbsolute());
        $this->assertTrue(Path::of('a/b')->isRelative());
        $this->assertFalse(Path::of('/')->isRelative());
        $this->assertFalse(Path::of('/a/b')->isRelative());
    }

    public function test_endsWith(): void
    {
        $this->assertFalse(Path::of('')->endsWith('/'));
        $this->assertTrue(Path::of('/')->endsWith('/'));
        $this->assertTrue(Path::of('foo/bar.txt')->endsWith('.txt'));
        $this->assertFalse(Path::of('foo/bar.txt')->endsWith('.php'));
        $this->assertTrue(Path::of('foo/bar/')->endsWith('/'));
    }

    public function test_segments_not_normalized(): void
    {
        $this->assertSame([], Path::of('/')->segments(false));
        $this->assertSame(['a', '.', 'b', '..', 'c'], Path::of('a/./b/../c')->segments(false));
    }

    public function test_segments_normalized(): void
    {
        $this->assertSame([], Path::of('/')->segments(true));
        $this->assertSame(['a', 'c'], Path::of('a/./b/../c')->segments(true));
        $this->assertSame(['a', 'c'], Path::of('/a/b/../c/./')->segments(true));
        $this->assertSame(['a', 'b', 'c'], Path::of('a/b/c')->segments(true));
    }

    public function test_normalize(): void
    {
        $this->assertSame('a/c', Path::of('a/./b/../c')->normalize());
        $this->assertSame('a/b/c', Path::of('a/b/c')->normalize());
        $this->assertSame('a', Path::of('a/b/..')->normalize()); // edge: pops empty
        $this->assertSame('/a/c', Path::of('/a/b/../c/./')->normalize());
    }

    public function test_append(): void
    {
        $this->assertSame('a/b/c', Path::of('a/b')->append('/c')->toString());
        $this->assertSame('a/b/c/', Path::of('a/b')->append('c/')->toString());
        $this->assertSame('a/b/c/d', Path::of('a/b')->append('c/d')->toString());
        $this->assertSame('a/e', Path::of('a')->append(Path::of('e'))->toString());
    }

    public function test_toString_and___toString(): void
    {
        $path = Path::of('foo/bar');
        $this->assertSame('foo/bar', $path->toString());
        $this->assertSame('foo/bar', (string)$path);
    }

    public function test_empty_path(): void
    {
        $path = Path::of('');
        $this->assertFalse($path->isAbsolute());
        $this->assertTrue($path->isRelative());
        $this->assertSame([], $path->segments());
        $this->assertSame('', $path->toString());
    }

    public function test_toStorable(): void
    {
        $storable = Path::of('/tmp')->toStorable();
        $this->assertInstanceOf(Directory::class, $storable);
        $this->assertSame('/tmp', $storable->pathname);
    }
}
