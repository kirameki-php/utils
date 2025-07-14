<?php declare(strict_types=1);

namespace Kirameki\Dumper\Handlers;

class NullHandler extends Handler
{
    /**
     * @param mixed $var
     * @return string
     */
    public function handle(mixed $var): string
    {
        return $this->colorizeScalar('null');
    }
}
