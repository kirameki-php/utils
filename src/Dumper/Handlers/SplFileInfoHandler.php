<?php declare(strict_types=1);

namespace Kirameki\Dumper\Handlers;

use Kirameki\Dumper\ObjectTracker;
use SplFileInfo;

class SplFileInfoHandler extends ClassHandler
{
    /**
     * @param SplFileInfo $var
     * @inheritDoc
     */
    public function handle(object $var, int $id, int $depth, ObjectTracker $tracker): string
    {
        return
            $this->colorizeName($var::class) . ' ' .
            $this->colorizeComment("#$id") . ' ' .
            $this->colorizeScalar($var->getPathname());
    }
}
