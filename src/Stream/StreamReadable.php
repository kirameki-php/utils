<?php declare(strict_types=1);

namespace Kirameki\Stream;

interface StreamReadable extends Streamable
{
    /**
     * @param int<1, max> $length
     * @return string
     */
    function read(int $length): string;

    /**
     * @return bool
     */
    function isEof(): bool;
}
