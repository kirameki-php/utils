<?php declare(strict_types=1);

namespace Kirameki\Stream;

use function ftruncate;
use function fwrite;

trait CanWrite
{
    use CanLock;
    use ThrowsError;

    /**
     * @return resource
     */
    abstract public function getResource(): mixed;

    /**
     * @param string $data
     * @param int<0, max>|null $length
     * @return int
     */
    public function write(string $data, ?int $length = null): int
    {
        $bytesWritten = @fwrite($this->getResource(), $data, $length);
        if ($bytesWritten === false) {
            $this->throwLastError([
                'data' => $data,
                'length' => $length,
            ]);
        }
        return $bytesWritten;
    }

    /**
     * @param int<0, max> $size
     * @return $this
     */
    public function truncate(int $size = 0): static
    {
        ftruncate($this->getResource(), $size);
        return $this;
    }
}
