<?php declare(strict_types=1);

namespace Tests\Kirameki\Stream;

use Kirameki\Stream\FileReader;
use Kirameki\Stream\FileWriter;

class FileWriterTest extends TestCase
{
    public function test_construct(): void
    {
        $file = __DIR__ . '/samples/write.txt';
        $stream = new FileWriter($file);
        self::assertTrue($stream->isOpen());
    }

    public function test_write_on_append(): void
    {
        $file = __DIR__ . '/samples/append.txt';
        $streamWrite = new FileWriter($file, true);
        $streamWrite->write('b');
        try {
            $streamRead = new FileReader($file);
            self::assertNotSame('ab', $streamRead->read(5));
        } finally {
            $streamWrite->truncate(7);
        }
    }
}
