<?php declare(strict_types=1);

namespace Tests\Kirameki\Stream;

use Kirameki\Stream\FileStream;
use function file_put_contents;

class FileStreamTest extends TestCase
{
    public function test_construct(): void
    {
        $path = __DIR__ . '/samples/read.txt';
        $stream = new FileStream($path);
        self::assertFalse($stream->isEof());
        self::assertTrue($stream->isOpen());
        self::assertSame($path, $stream->getUri());
        self::assertSame('c+b', $stream->getMode());
        $stream->close();
        self::assertTrue($stream->isClosed());
    }

    public function test_write_without_append(): void
    {
        $path = '/tmp/close.txt';
        file_put_contents($path, 'abc');
        $stream = new FileStream($path);
        $stream->write('def');
        self::assertTrue($stream->seek(0));
        self::assertSame('def', $stream->read(5));
        self::assertSame(3, $stream->currentPosition());
        $stream->close();
        self::assertTrue($stream->isClosed());
    }

    public function test_write_with_append(): void
    {
        $path = '/tmp/close.txt';
        file_put_contents($path, 'abc');
        $stream = new FileStream($path, 'ab+');
        $stream->write('def');
        self::assertTrue($stream->seek(0));
        self::assertSame('abcdef', $stream->read(6));
        self::assertSame(6, $stream->currentPosition());
        $stream->close();
    }
}
