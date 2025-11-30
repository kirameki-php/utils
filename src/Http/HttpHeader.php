<?php declare(strict_types=1);

namespace Kirameki\Http;

use function implode;

class HttpHeader
{
    /**
     * @param string $name
     * @param list<string> $values
     */
    public function __construct(
        public readonly string $name,
        public array $values,
    ) {
    }

    /**
     * @return string
     */
    public function toString(): string
    {
        return $this->name . ': ' . implode(', ', $this->values);
    }
}
