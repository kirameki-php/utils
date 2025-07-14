<?php declare(strict_types=1);

namespace Kirameki\Collections\Utils;

use Countable;
use IteratorAggregate;
use Kirameki\Core\Exceptions\InvalidArgumentException;
use Traversable;

/**
 * @implements IteratorAggregate<int, mixed>
 */
final readonly class Range implements Countable, IteratorAggregate
{
    /**
     * @param int $start
     * Starting number of the range.
     * @param int $end
     * Ending number of the range.
     * @param bool $includeEnd
     * Whether to include the end number or not.
     * Defaults to **true**.
     */
    public function __construct(
        private int $start,
        private int $end,
        private bool $includeEnd = true,
    )
    {
        if ($this->min() > $this->max()) {
            $message = $includeEnd
                ? '$start must be <= $end.'
                : '$start must be < $end when end is not included.';
            throw new InvalidArgumentException("{$message} Got: {$start} -> {$end}.", [
                'lowerBound' => $start,
                'upperBound' => $end,
                'includeEnd' => $includeEnd,
            ]);
        }
    }

    /**
     * @return int
     */
    public function count(): int
    {
        return $this->max() - $this->min();
    }

    /**
     * @return Traversable<int, int>
     */
    public function getIterator(): Traversable
    {
        $min = $this->min();
        $max = $this->max();

        $cursor = $min;
        while ($max >= $cursor) {
            yield $cursor;
            ++$cursor;
        }
    }

    /**
     * @return array<int, int>
     */
    public function all(): array
    {
        return iterator_to_array($this);
    }

    /**
     * @return int
     */
    public function min(): int
    {
        return $this->start;
    }

    /**
     * @return int
     */
    public function max(): int
    {
        return $this->includeEnd
            ? $this->end
            : $this->end - 1;
    }

    /**
     * @return bool
     */
    public function includesEnd(): bool
    {
        return $this->includeEnd;
    }
}
