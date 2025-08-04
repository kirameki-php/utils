<?php declare(strict_types=1);

namespace Tests\Kirameki\Stream;

use Kirameki\Stream\TempStream;

class TempStreamTest extends TestCase
{
    public function test_construct(): void
    {
        $stream = new TempStream();
        self::assertFalse($stream->isEof());
        self::assertTrue($stream->isOpen());
        self::assertStringStartsWith('php://temp', $stream->getUri());
        self::assertSame('w+b', $stream->getMode());
        $stream->close();
    }

    public function test_construct_with_max_memory(): void
    {
        $stream = new TempStream(1024);
        self::assertFalse($stream->isEof());
        self::assertTrue($stream->isOpen());
        self::assertStringStartsWith('php://temp', $stream->getUri());
        self::assertSame('w+b', $stream->getMode());
        $stream->close();
    }
}
