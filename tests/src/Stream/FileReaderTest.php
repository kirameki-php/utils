<?php declare(strict_types=1);

namespace Tests\Kirameki\Stream;

use ErrorException;
use Kirameki\Stream\FileReader;

class FileReaderTest extends TestCase
{
    public function test_construct(): void
    {
        $stream = new FileReader(__DIR__ . '/samples/read.txt');
        self::assertFalse($stream->isEof());
        self::assertTrue($stream->isOpen());
        self::assertSame('rb', $stream->getMode());
    }

    public function test_with_no_such_file(): void
    {
        $file = __DIR__ . '/samples/invalid.txt';
        $this->expectException(ErrorException::class);
        $this->expectExceptionMessage("fopen({$file}): Failed to open stream: No such file or directory");
        new FileReader($file);
    }
}
