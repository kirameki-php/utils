<?php

declare(strict_types=1);

namespace Kirameki\File;

use Generator;
use Kirameki\Core\Exceptions\RuntimeException;
use const GLOB_ERR;
use const GLOB_NOESCAPE;

class FileSystem
{
    /**
     * Returns an array of files matching the given glob pattern.
     *
     * @param string $pattern The glob pattern to match files against.
     * @param bool $sort Whether to sort the results. Defaults to true.
     * @return Generator<int, string>
     *
     */
    public function glob(
        string $pattern,
        bool $sort = true,
        bool $dirOnly = false,
    ): Generator {
        $flags = GLOB_ERR | GLOB_NOESCAPE;
        if (!$sort) {
            $flags |= GLOB_NOSORT;
        }
        if ($dirOnly) {
            $flags |= GLOB_ONLYDIR;
        }

        $files = glob($pattern, $flags);

        if ($files === false) {
            // get underlying error
            $error = error_get_last();
            throw new RuntimeException("Failed to glob pattern '{$pattern}': " . ($error['message'] ?? ''), [
                'pattern' => $pattern,
                'flags' => $flags,
            ]);
        }

        foreach ($files as $file) {
            yield $file;
        }
    }
}
