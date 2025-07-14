<?php declare(strict_types=1);

namespace Tests\Kirameki\Collections\References;

class SimpleClass
{
    public function __construct(
        public bool $b = true,
        public int $i = 1,
        public float $f = 1.0,
    )
    {
    }
}
