<?php declare(strict_types=1);

namespace Kirameki\Core;

use Closure;

/**
 * @template T
 */
final class Lazy
{
    /**
     * @var T
     */
    public mixed $value {
        get => $this->resolved
            ? $this->value
            : $this->value = $this->resolve();
    }

    /**
     * @var bool
     */
    public private(set) bool $resolved = false;

    /**
     * @param Closure(): T $resolver
     */
    public function __construct(
        protected Closure $resolver,
    ) {
    }

    /**
     * @return T
     */
    private function resolve(): mixed
    {
        $value = ($this->resolver)();
        $this->resolved = true;
        return $value;
    }
}
