<?php declare(strict_types=1);

namespace Kirameki\Http;

use Kirameki\Collections\Utils\Arr;
use Kirameki\Collections\Vec;
use Kirameki\Exceptions\RuntimeException;
use function array_map;
use function implode;
use function in_array;
use function strtolower;

abstract class HttpHeaders
{
    /**
     * @param array<string, HttpHeader> $entries
     */
    public function __construct(
        protected array $entries = [],
    ) {
        $this->merge($entries);
    }

    /**
     * @return array<string, HttpHeader>
     */
    public function all(): array
    {
        return $this->entries;
    }

    /**
     * @param string $name
     * @return bool
     */
    public function exists(string $name): bool
    {
        return $this->getOrNull($name) !== null;
    }

    /**
     * @param string $name
     * @param string $expected
     * @return bool
     */
    public function contains(string $name, string $expected): bool
    {
        return in_array($expected, $this->entries[$this->toKey($name)]->values ?? [], true);
    }

    /**
     * @param string $name
     * @return Vec<string>
     */
    public function get(string $name): Vec
    {
        return $this->getOrNull($name) ?? throw new RuntimeException("Header '{$name}' does not exist.");
    }

    /**
     * @param string $name
     * @return Vec<string>|null
     */
    public function getOrNull(string $name): ?Vec
    {
        $header = $this->entries[$this->toKey($name)] ?? null;

        return $header !== null
            ? new Vec($header->values)
            : null;
    }

    /**
     * @param string $name
     * @return string
     */
    public function first(string $name): string
    {
        return $this->firstOrNull($name) ?? throw new RuntimeException("Header '{$name}' does not exist.");
    }

    /**
     * @param string $name
     * @return string|null
     */
    public function firstOrNull(string $name): ?string
    {
        return $this->entries[$this->toKey($name)]->values[0] ?? null;
    }

    /**
     * @param string $name
     * @return string
     */
    public function last(string $name): string
    {
        return $this->lastOrNull($name) ?? throw new RuntimeException("Header '{$name}' does not exist.");
    }

    /**
     * @param string $name
     * @return string|null
     */
    public function lastOrNull(string $name): ?string
    {
        return Arr::last($this->entries[$this->toKey($name)]->values ?? []);
    }

    /**
     * @param string $name
     * @return string
     */
    public function single(string $name): string
    {
        return Arr::single($this->entries[$this->toKey($name)]->values ?? []);
    }

    /**
     * @param string $name
     * @param string $value
     * @return void
     */
    public function add(string $name, string $value): void
    {
        $key = $this->toKey($name);

        isset($this->entries[$key])
            ? $this->entries[$key]->values[] = $value
            : $this->entries[$key] = new HttpHeader($name, [$value]);
    }

    /**
     * @param string $name
     * @param string $value
     * @return void
     */
    public function set(string $name, string $value): void
    {
        $this->entries[$this->toKey($name)] = new HttpHeader($name, [$value]);
    }

    /**
     * @param array<string, HttpHeader> $entries
     * @return void
     */
    public function merge(array $entries): void
    {
        foreach ($entries as $name => $entry) {
            foreach ($entry->values as $value) {
                $this->add($name, $value);
            }
        }
    }

    /**
     * @param string $name
     * @return void
     */
    public function delete(string $name): void
    {
        unset($this->entries[$this->toKey($name)]);
    }

    /**
     * @return string
     */
    public function toString(): string
    {
        return implode("\r\n", array_map(static fn($h) => $h->toString(), $this->entries));
    }

    /**
     * @return string
     */
    public function __toString(): string
    {
        return $this->toString();
    }

    protected function toKey(string $name): string
    {
        return strtolower($name);
    }
}
