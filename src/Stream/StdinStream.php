<?php declare(strict_types=1);

namespace Kirameki\Stream;

class StdinStream extends ResourceStreamable implements StreamReadable
{
    use CanRead;

    public function __construct()
    {
        parent::__construct($this->open('php://stdin', 'r'));
    }
}
