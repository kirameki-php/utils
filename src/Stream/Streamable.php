<?php declare(strict_types=1);

namespace Kirameki\Stream;

interface Streamable
{
    /**
     * @return resource
     */
    public function getResource(): mixed;
}
