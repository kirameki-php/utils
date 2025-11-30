<?php declare(strict_types=1);

namespace Kirameki\Core;

use Kirameki\Exceptions\NotSupportedException;

trait StaticClass
{
    final public function __construct()
    {
        throw new NotSupportedException('Cannot instantiate static class: ' . static::class);
    }
}
