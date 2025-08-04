<?php declare(strict_types=1);

namespace Tests\Kirameki\Stream;

use Kirameki\Stream\StdoutStream;

class StdoutStreamTest extends TestCase
{
    public function test_construct(): void
    {
        $stream = new StdoutStream();
        self::assertTrue($stream->isOpen());
        self::assertSame('php://stdout', $stream->getUri());
        self::assertSame('w', $stream->getMode());
        $stream->close();
    }
}
