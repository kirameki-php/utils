<?php declare(strict_types=1);

namespace Kirameki\Dumper\Handlers;

use SouthPointe\Ansi\Codes\Color;

class NullHandler extends Handler
{
    /**
     * @param mixed $var
     * @return string
     */
    public function handle(mixed $var): string
    {
        return $this->colorize('null', Color::Orange3);
    }
}
