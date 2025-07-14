<?php declare(strict_types=1);

use Kirameki\Dumper\Config;
use Kirameki\Dumper\Dumper;

if (!function_exists('dump'))
{
    /**
     * @param mixed ...$vars
     * @return void
     */
    function dump(mixed ...$vars): void
    {
        Dumper::getInstance()->dump(...$vars);
    }
}
