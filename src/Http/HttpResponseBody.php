<?php declare(strict_types=1);

namespace Kirameki\Http;

use Stringable;
use function flush;

class HttpResponseBody implements Stringable
{
    public function __construct(
        public string $body = '',
    ) {
    }

    /**
     * @return void
     */
    public function send(): void
    {
        echo $this->toString();
        flush();
    }

    /**
     * @return string
     */
    public function toString(): string
    {
        return $this->body;
    }

    /**
     * @return string
     */
    public function __toString(): string
    {
        return $this->toString();
    }
}
