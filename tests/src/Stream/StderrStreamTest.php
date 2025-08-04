<?php declare(strict_types=1);

namespace Tests\Kirameki\Stream;

use Kirameki\Stream\StderrStream;

class StderrStreamTest extends TestCase
{
    public function test_construct(): void
    {
        $stream = new StderrStream();
        self::assertTrue($stream->isOpen());
        self::assertSame('php://stderr', $stream->getUri());
        self::assertSame('w', $stream->getMode());
        $stream->close();
    }
}
