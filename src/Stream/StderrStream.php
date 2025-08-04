<?php declare(strict_types=1);

namespace Kirameki\Stream;

class StderrStream extends ResourceStreamable implements StreamWritable
{
    use CanWrite;

    public function __construct()
    {
        parent::__construct($this->open('php://stderr', 'w'));
    }
}
