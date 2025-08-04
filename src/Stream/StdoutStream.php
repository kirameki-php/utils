<?php declare(strict_types=1);

namespace Kirameki\Stream;

class StdoutStream extends ResourceStreamable implements StreamWritable
{
    use CanWrite;

    public function __construct()
    {
        parent::__construct($this->open('php://stdout', 'w'));
    }
}
