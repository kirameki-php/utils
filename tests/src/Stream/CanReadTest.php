<?php declare(strict_types=1);

namespace Tests\Kirameki\Stream;

use Kirameki\Stream\Exceptions\StreamErrorException;
use Kirameki\Stream\FileReader;
use Kirameki\Stream\FileWriter;
use Kirameki\Stream\MemoryStream;
use function dump;
use function error_clear_last;
use function file_get_contents;

class CanReadTest extends TestCase
{
    public function test_read(): void
    {
        $stream = new FileReader(__DIR__ . '/samples/read.txt');
        self::assertSame("123\n", $stream->read(5));
        self::assertSame('', $stream->read(5));
    }

    public function test_read_with_empty(): void
    {
        $stream = new FileReader(__DIR__ . '/samples/empty.txt');
        self::assertSame('', $stream->read(5));
    }

    public function test_read_fail_test(): void
    {
        $this->expectExceptionMessage('fread(): Read of 8192 bytes failed with errno=21 Is a directory');
        $this->expectException(StreamErrorException::class);
        $stream = new FileReader(__DIR__ . '/samples/');
        $stream->read(1);
    }

    public function test_readLine(): void
    {
        $stream = new FileReader(__DIR__ . '/samples/read.txt');
        // specify length
        self::assertSame('1', $stream->readLine(1));
        // read to end
        self::assertSame('23', $stream->readLine());
        // over read
        self::assertSame('', $stream->readLine());
    }

    public function test_readLine_fail_test(): void
    {
        $this->expectExceptionMessage('stream_get_line(): Read of 8192 bytes failed with errno=21 Is a directory');
        $this->expectException(StreamErrorException::class);
        $stream = new FileReader(__DIR__ . '/samples');
        $stream->readLine();
    }

    public function test_readToEnd(): void
    {
        $stream = new FileReader(__DIR__ . '/samples/read.txt');
        self::assertSame("123\n", $stream->readToEnd());
        $stream->seek(1);
        self::assertSame("23\n", $stream->readToEnd());
    }

    public function test_readToEnd_with_buffer(): void
    {
        $stream = new FileReader(__DIR__ . '/samples/read.txt');
        self::assertSame("123\n", $stream->readToEnd(1));
    }
}
