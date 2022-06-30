<?php declare(strict_types=1);

namespace Kirameki\Utils\Exception;

use LogicException;

class DuplicateKeyException extends LogicException
{
    /**
     * @param array-key $key
     * @param iterable<array-key, mixed> $iterable
     */
    public function __construct(
        public readonly string|int $key,
        public readonly iterable $iterable,
    )
    {
        parent::__construct("Tried to overwrite existing key: " . $key);
    }
}
