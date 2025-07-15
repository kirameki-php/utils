<?php declare(strict_types=1);

namespace Kirameki\System;

use Override;

class SleepMock extends Sleep
{
    /**
     * @inheritDoc
     */
    #[Override]
    protected function executeUsleep(int $microseconds): void
    {
        // do nothing
    }
}
