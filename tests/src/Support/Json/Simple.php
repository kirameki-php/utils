<?php declare(strict_types=1);

namespace Tests\Kirameki\Utils\Support\Json;

use DateTime;

class Simple
{
    public function __construct(
        public bool $b = true,
        public int $i = 1,
        public float $f = 1.0,
    )
    {
    }
}
