<?php declare(strict_types=1);

namespace Kirameki\Collections\Exceptions;

use Kirameki\Collections\Utils\Arr;
use Throwable;
use function is_string;

class ExcessKeyException extends CollectionException
{
    /**
     * @param array<int, array-key> $excessKeys
     * @param iterable<string, mixed>|null $context
     * @param int $code
     * @param Throwable|null $previous
     */
    public function __construct(
        array $excessKeys,
        ?iterable $context = null,
        int $code = 0,
        ?Throwable $previous = null,
    )
    {
        $excessFormatted = Arr::map($excessKeys, fn($k) => is_string($k) ? "'{$k}'" : $k);
        $excessJoined = Arr::join($excessFormatted, ', ', '[', ']');
        $message = "Keys: {$excessJoined} should not exist.";

        parent::__construct($message, $context, $code, $previous);
    }
}
