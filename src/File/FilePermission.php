<?php

declare(strict_types=1);

namespace Kirameki\File;

class FilePermission
{
    public function __construct(
        protected readonly int $mode,
    ) {

    }
}
