<?php declare(strict_types=1);

namespace Kirameki\Dumper\Handlers;

use Kirameki\Dumper\ObjectTracker;
use UnitEnum;

class EnumHandler extends ClassHandler
{
    /**
     * @param UnitEnum $var
     * @inheritDoc
     */
    public function handle(object $var, int $id, int $depth, ObjectTracker $tracker): string
    {
        return
            $this->colorizeName($var::class . "::{$var->name}") . ' ' .
            $this->colorizeComment("#{$id}");
    }
}
