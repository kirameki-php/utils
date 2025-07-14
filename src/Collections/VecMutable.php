<?php declare(strict_types=1);

namespace Kirameki\Collections;

use Kirameki\Collections\Exceptions\IndexOutOfBoundsException;
use Kirameki\Collections\Utils\Arr;
use Override;
use function count;

/**
 * @template TValue
 * @extends Vec<TValue>
 */
class VecMutable extends Vec
{
    /**
     * @use MutatesSelf<int, TValue>
     */
    use MutatesSelf {
        offsetSet as traitOffsetSet;
        offsetUnset as traitOffsetUnset;
    }

    /**
     * @param iterable<int, TValue> $items
     */
    public function __construct(iterable $items = [])
    {
        parent::__construct(Arr::from($items));
    }

    /**
     * @inheritDoc
     */
    #[Override]
    public function instantiate(mixed $iterable): static
    {
        return new static($iterable);
    }

    /**
     * @inheritDoc
     * @param int|null $offset
     * @param TValue $value
     * @return void
     */
    #[Override]
    public function offsetSet(mixed $offset, mixed $value): void
    {
        $this->ensureOffsetIsIndex($offset);

        $ref = $this->getItemsAsRef();
        $size = count($ref);
        if ($offset > $size) {
            throw new IndexOutOfBoundsException("Can not assign to a non-existing index. (size: {$size} index: {$offset})", [
                'this' => $this,
                'offset' => $offset,
                'size' => $size,
            ]);
        }

        self::traitOffsetSet($offset, $value);
    }

    /**
     * @inheritDoc
     * @param int $offset
     * @return void
     */
    #[Override]
    public function offsetUnset(mixed $offset): void
    {
        $this->ensureOffsetIsIndex($offset);
        self::traitOffsetUnset($offset);
    }

    /**
     * Returns an immutable copy of this map.
     *
     * @return Vec<TValue>
     */
    public function immutable(): Vec
    {
        return new Vec($this->items);
    }
}
