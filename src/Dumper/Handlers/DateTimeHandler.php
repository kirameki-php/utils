<?php declare(strict_types=1);

namespace Kirameki\Dumper\Handlers;

use DateTime;
use Kirameki\Dumper\ObjectTracker;

class DateTimeHandler extends ClassHandler
{
    /**
     * @param DateTime $var
     * @inheritDoc
     */
    public function handle(object $var, int $id, int $depth, ObjectTracker $tracker): string
    {
        return
            $this->colorizeName($var::class) . ' ' .
            $this->colorizeComment("#$id") . ' ' .
            $this->colorizeScalar($var->format($this->config->dateTimeFormat));
    }
}
