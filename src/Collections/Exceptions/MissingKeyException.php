<?php declare(strict_types=1);

namespace Kirameki\Collections\Exceptions;

use Kirameki\Collections\Utils\Arr;
use Throwable;
use function is_string;

class MissingKeyException extends CollectionException
{
    /**
     * @param array<int, array-key> $missingKeys
     * @param iterable<string, mixed>|null $context
     * @param int $code
     * @param Throwable|null $previous
     */
    public function __construct(
        array $missingKeys,
        ?iterable $context = null,
        int $code = 0,
        ?Throwable $previous = null,
    )
    {
        $missingFormatted = Arr::map($missingKeys, fn($k) => is_string($k) ? "'{$k}'" : $k);
        $missingJoined = Arr::join($missingFormatted, ', ', '[', ']');
        $message = "Keys: {$missingJoined} did not exist.";

        parent::__construct($message, $context, $code, $previous);
    }
}
