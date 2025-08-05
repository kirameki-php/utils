<?php declare(strict_types=1);

namespace Kirameki\Stream;

trait CanScan
{
    use CanSeek;
    use CanRead;

    /**
     * @return string
     */
    public function readFromStartToEnd(): string
    {
        return $this->rewind()->readToEnd();
    }

    /**
     * @param StreamWritable $writer
     * @param int<1, max> $buffer
     * @param bool $rewind
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
}
