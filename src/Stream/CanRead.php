<?php declare(strict_types=1);

namespace Kirameki\Stream;

use function error_get_last;
use function feof;
use function fread;
use function is_string;
use function stream_get_line;
use const PHP_INT_MAX;

trait CanRead
{
    use CanLock;
    use ThrowsError;

    /**
     * @return resource
     */
    abstract public function getResource(): mixed;

    /**
     * @param int<1, max> $length
     * @return string
     */
    public function read(int $length): string
    {
        $data = @fread($this->getResource(), $length);
        if ($data === false) {
            $this->throwLastError([
                'length' => $length,
            ]);
        }
        return $data;
    }

    /**
     * @param int<1, max> $length
     * @param string $ending
     * @return string
     */
    public function readLine(int $length = PHP_INT_MAX, string $ending = "\n"): string
    {
        $stream = $this->getResource();
        $line = @stream_get_line($stream, $length, $ending);
        if ($line === false && error_get_last()) {
            $this->throwLastError([
                'length' => $length,
                'ending' => $ending,
            ]);
        }
        return (string) $line;
    }

    /**
     * @param int<1, max> $buffer
     * @return string
     */
    public function readToEnd(int $buffer = 4096): string
    {
        $string = '';
        while(true) {
            $line = $this->read($buffer);
            if ($line === '') {
                break;
            }
            $string .= $line;
        }
        return $string;
    }

    /**
     * @return string
     */
    public function readFromStartToEnd(): string
    {
        return $this->rewind()->readToEnd();
    }

    /**
     * @param StreamWritable $writer
     * @param int $buffer
     * @return int
     */
    public function copyTo(StreamWritable $writer, int $buffer = 4096, bool $rewind = true): int
    {
        if ($rewind) {
            $this->rewind();
        }

        $size = 0;
        while (!$this->isEof()) {
            $size += $writer->write($this->read($buffer));
        }

        return $size;
    }

    /**
     * @return bool
     */
    public function isEof(): bool
    {
        return feof($this->getResource());
    }

    /**
     * @return bool
     */
    public function isNotEof(): bool
    {
        return !$this->isEof();
    }
}
