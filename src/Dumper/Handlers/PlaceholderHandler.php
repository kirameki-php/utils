<?php declare(strict_types=1);

namespace Kirameki\Dumper\Handlers;

use Kirameki\Dumper\ObjectTracker;
use Kirameki\Dumper\Placeholder;

class PlaceholderHandler extends ClassHandler
{
    /**
     * @param Placeholder $var
     * @inheritDoc
     */
    public function handle(object $var, int $id, int $depth, ObjectTracker $tracker): string
    {
        return $this->colorizeComment('<undefined>');
    }
}
