<?php declare(strict_types=1);

namespace Kirameki\Stream;

interface StreamReadable extends Streamable
{
    /**
     * @param int<0, max> $length
     * @return string
     */
    function read(int $length): string;

    /**
     * @param StreamWritable $writer
     * @return int
     */
    function copyTo(StreamWritable $writer): int;

    /**
     * @return bool
     */
    function isEof(): bool;
}
