<?php declare(strict_types=1);

namespace Tests\Kirameki\Stream;

use Kirameki\Stream\StdinStream;

class StdinStreamTest extends TestCase
{
    public function test_construct(): void
    {
        $stream = new StdinStream();
        self::assertTrue($stream->isOpen());
        self::assertSame('php://stdin', $stream->getUri());
        self::assertSame('r', $stream->getMode());
        $stream->close();
    }
}
