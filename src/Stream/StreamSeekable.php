<?php declare(strict_types=1);

namespace Kirameki\Stream;

interface StreamSeekable extends Streamable
{
    /**
     * @return int
     */
    function currentPosition(): int;

    /**
     * @return static
     */
    function rewind(): static;

    /**
     * @return $this
     */
    function fastForward(): static;

    /**
     * @param int $offset
     * @param int $whence
     * @return bool
     */
    function seek(int $offset, int $whence = SEEK_SET): bool;
}
