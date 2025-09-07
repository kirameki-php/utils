<?php

namespace Kirameki\Core;

use Stringable;
use function count;
use function implode;

class MediaType implements Stringable
{
    /**
     * @param string $type
     * @param string $subtype
     * @param array<string, string> $parameters
     */
    public function __construct(
        public readonly string $type,
        public readonly string $subtype,
        public readonly array $parameters = [],
    ) {
    }

    /**
     * @return string
     */
    public function toString(): string
    {
        $str = "{$this->type}/{$this->subtype}";
        if (count($this->parameters) > 0) {
            $params = [];
            foreach ($this->parameters as $key => $value) {
                $params[] = "{$key}={$value}";
            }
            $str .= ';' . implode('; ', $params);
        }
        return $str;
    }

    /**
     * @return string
     */
    public function __toString(): string
    {
        return $this->toString();
    }
}
