<?php declare(strict_types=1);

namespace Kirameki\Dumper;

use function array_key_exists;

class ObjectTracker
{
    /**
     * @param array<int, int> $processedIds
     * @param array<int, true> $circularIds
     */
    public function __construct(
        protected array $processedIds = [],
        protected array $circularIds = [],
    )
    {
    }

    /**
     * @param int $id
     * @return void
     */
    public function markAsProcessed(int $id): void
    {
        $this->processedIds[$id] ??= 0;
        $this->processedIds[$id] += 1;
        $this->circularIds[$id] = true;
    }

    /**
     * @param int $id
     * @return bool
     */
    public function isProcessed(int $id): bool
    {
        return array_key_exists($id, $this->processedIds);
    }

    /**
     * @param int $id
     * @return int
     */
    public function getProcessedCount(int $id): int
    {
        return $this->processedIds[$id] ?? 0;
    }

    /**
     * @param int $id
     * @return bool
     */
    public function isCircular(int $id): bool
    {
        return array_key_exists($id, $this->circularIds);
    }

    /**
     * @param int $id
     * @return void
     */
    public function clearCircular(int $id): void
    {
        unset($this->circularIds[$id]);
    }
}
