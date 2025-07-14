<?php declare(strict_types=1);

namespace Tests\Kirameki\Collections\References;

use Random\Engine;
use Random\Randomizer;
use function pack;

class FixedNumEngine implements Engine
{
    public function generate(): string
    {
        return pack('P', 0x0);
    }

    public static function inRandomizer(): Randomizer
    {
        return new Randomizer(new self());
    }
}
