<?php declare(strict_types=1);

namespace Kirameki\Stream;

class FileWriter extends ResourceStreamable implements StreamWritable
{
    use CanWrite;

    /**
     * @param string $path
     * @param bool $append
     */
    public function __construct(string $path, bool $append = false)
    {
        parent::__construct($this->open($path, $append ? 'ab' : 'wb'));
    }
}
