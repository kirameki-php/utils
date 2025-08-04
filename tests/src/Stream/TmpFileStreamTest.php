<?php declare(strict_types=1);

namespace Tests\Kirameki\Stream;

use Kirameki\Stream\TmpFileStream;

class TmpFileStreamTest extends TestCase
{
    public function test_construct(): void
    {
        $stream = new TmpFileStream();
        self::assertFalse($stream->isEof());
        self::assertTrue($stream->isOpen());
        self::assertStringStartsWith('/tmp/php', $stream->getUri());
        self::assertSame('r+b', $stream->getMode());
        self::assertFileExists($stream->getUri());
        // Closing will remove tmpfile
        $stream->close();
        self::assertFileDoesNotExist($stream->getUri());
    }
}
