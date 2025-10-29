<?php declare(strict_types=1);

namespace Kirameki\Stream;

use function fclose;
use function fopen;
use function is_resource;
use function stream_get_meta_data;

abstract class ResourceStreamable implements Streamable
{
    use ThrowsError;

    /**
     * @var resource
     */
    protected readonly mixed $resource;

    /**
     * @var array<string, mixed>
     */
    protected readonly array $meta;

    /**
     * @param resource $resource
     */
    public function __construct(mixed $resource)
    {
        $this->resource = $resource;
        $this->meta = stream_get_meta_data($this->resource);
    }

    /**
     * @return resource
     */
    protected function open(string $path, string $mode)
    {
        $stream = @fopen($path, $mode);
        if ($stream === false) {
            $this->throwLastError();
        }
        return $stream;
    }

    /**
     * @return resource
     */
    public function getResource(): mixed
    {
        return $this->resource;
    }

    /**
     * @return array<string, mixed>
     */
    public function getMetadata(): array
    {
        return $this->meta;
    }

    /**
     * @return string
     */
    public function getUri(): string
    {
        return $this->meta['uri'] ?? '';
    }

    /**
     * @return string
     */
    public function getMode(): string
    {
        return $this->meta['mode'];
    }

    /**
     * @return bool
     */
    public function isOpen(): bool
    {
        return is_resource($this->resource);
    }

    /**
     * @return bool
     */
    public function isClosed(): bool
    {
        return ! $this->isOpen();
    }

    /**
     * @return bool
     */
    public function close(): bool
    {
        return fclose($this->resource);
    }

    /**
     * @return array<string, mixed>|null
     */
    public function __debugInfo(): ?array
    {
        return [
            'uri' => $this->meta['uri'] ?? '',
            'mode' => $this->meta['mode'],
        ];
    }
}
