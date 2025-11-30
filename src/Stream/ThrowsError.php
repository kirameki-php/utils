<?php declare(strict_types=1);

namespace Kirameki\Stream;

use Kirameki\Exceptions\UnreachableException;
use Kirameki\Stream\Exceptions\StreamErrorException;
use function error_clear_last;
use function error_get_last;

trait ThrowsError
{
    /**
     * @param iterable<string, mixed>|null $context
     * @return never
     */
    protected function throwLastError(
        ?iterable $context = null,
    ): never
    {
        $error = error_get_last() ?? throw new UnreachableException();
        error_clear_last();

        $context ??= [];
        $context += [
            'stream' => $this,
        ];

        throw new StreamErrorException(
            $error['message'],
            $error['type'],
            $error['file'],
            $error['line'],
            $context,
        );
    }
}
