<?php declare(strict_types=1);

namespace Kirameki\Text;

use Closure;
use JsonSerializable;
use Stringable;
use function basename;
use function dirname;
use function sprintf;

class StrObject implements JsonSerializable, Stringable
{
    protected static Str $ref;

    /**
     * @param string $value
     * @return static
     */
    public static function from(string $value): static
    {
        return new static($value);
    }

    /**
     * @param string $value
     */
    public function __construct(protected string $value = '')
    {
        static::$ref ??= new Str();
    }

    /**
     * @return string
     */
    public function jsonSerialize(): string
    {
        return $this->toString();
    }

    /**
     * @return string
     */
    public function __toString(): string
    {
        return $this->toString();
    }

    /**
     * Appends the given string(s) to the end of the current string.
     *
     * @param string ...$string
     * @return static
     */
    public function append(string ...$string): static
    {
        return new static(static::$ref::concat($this->value, ...$string));
    }

    /**
     * Appends the given string(s) to the end of the current string.
     * The string(s) will be formatted using sprintf().
     *
     * @param string $format
     * @param float|int|string ...$values
     * @return static
     */
    public function appendFormat(string $format, float|int|string ...$values): static
    {
        return new static($this->value . sprintf($format, ...$values));
    }

    /**
     * @see basename()
     *
     * Calls and returns the result of calling basename() on the current string.
     *
     * @param string $suffix
     * @return static
     */
    public function basename(string $suffix = ''): static
    {
        return new static(basename($this->value, $suffix));
    }

    /**
     * @see Str::between()
     *
     * @param string $from
     * @param string $to
     * @return static
     */
    public function between(string $from, string $to): static
    {
        return new static(static::$ref::between($this->value, $from, $to));
    }

    /**
     * @see Str::betweenFurthest()
     *
     * @param string $from
     * @param string $to
     * @return static
     */
    public function betweenFurthest(string $from, string $to): static
    {
        return new static(static::$ref::betweenFurthest($this->value, $from, $to));
    }

    /**
     * @see Str::betweenLast()
     *
     * @param string $from
     * @param string $to
     * @return static
     */
    public function betweenLast(string $from, string $to): static
    {
        return new static(static::$ref::betweenLast($this->value, $from, $to));
    }

    /**
     * @see Str::capitalize()
     *
     * @return static
     */
    public function capitalize(): static
    {
        return new static(static::$ref::capitalize($this->value));
    }

    /**
     * @see Str::chunk()
     *
     * @param int $size
     * @param int|null $limit
     * @return list<string>
     */
    public function chunk(int $size, ?int $limit = null): array
    {
        return static::$ref::chunk($this->value, $size, $limit);
    }

    /**
     * @param string $needle
     * @return bool
     */
    public function contains(string $needle): bool
    {
        return static::$ref::contains($this->value, $needle);
    }

    /**
     * @param array<string> $needles
     * @return bool
     */
    public function containsAll(array $needles): bool
    {
        return static::$ref::containsAll($this->value, $needles);
    }

    /**
     * @param array<string> $needles
     * @return bool
     */
    public function containsAny(array $needles): bool
    {
        return static::$ref::containsAny($this->value, $needles);
    }

    /**
     * @param string $pattern
     * @return bool
     */
    public function containsPattern(string $pattern): bool
    {
        return static::$ref::containsPattern($this->value, $pattern);
    }

    /**
     * @param string $substring
     * @param bool $overlapping
     * @return int
     */
    public function count(string $substring, bool $overlapping = false): int
    {
        return static::$ref::count($this->value, $substring, $overlapping);
    }

    /**
     * @return static
     */
    public function decapitalize(): static
    {
        return new static(static::$ref::decapitalize($this->value));
    }

    /**
     * @param int<1, max> $levels
     * @return static
     */
    public function dirname(int $levels = 1): static
    {
        return new static(dirname($this->value, $levels));
    }

    /**
     * @param string $substring
     * @return bool
     */
    public function doesNotContain(string $substring): bool
    {
        return static::$ref::doesNotContain($this->value, $substring);
    }

    /**
     * @param string $suffix
     * @return bool
     */
    public function doesNotEndWith(string $suffix): bool
    {
        return static::$ref::doesNotEndWith($this->value, $suffix);
    }

    /**
     * @param string $prefix
     * @return bool
     */
    public function doesNotStartWith(string $prefix): bool
    {
        return static::$ref::doesNotStartWith($this->value, $prefix);
    }

    /**
     * @param int $amount
     * @return static
     */
    public function dropFirst(int $amount): static
    {
        return new static(static::$ref::dropFirst($this->value, $amount));
    }

    /**
     * @param int $amount
     * @return static
     */
    public function dropLast(int $amount): static
    {
        return new static(static::$ref::dropLast($this->value, $amount));
    }

    /**
     * @param string $suffix
     * @return bool
     */
    public function endsWith(string $suffix): bool
    {
        return static::$ref::endsWith($this->value, $suffix);
    }

    /**
     * @param iterable<array-key, string> $suffixes
     * @return bool
     */
    public function endsWithAny(iterable $suffixes): bool
    {
        return static::$ref::endsWithAny($this->value, $suffixes);
    }

    /**
     * @param iterable<array-key, string> $suffixes
     * @return bool
     */
    public function endsWithNone(iterable $suffixes): bool
    {
        return static::$ref::endsWithNone($this->value, $suffixes);
    }

    /**
     * @param string $string
     * String to compare against.
     * @return bool
     */
    public function equals(string $string): bool
    {
        return static::$ref::equals($this->value, $string);
    }

    /**
     * @param iterable<array-key, string> $strings
     * Strings to compare against.
     * @return bool
     */
    public function equalsAny(iterable $strings): bool
    {
        return static::$ref::equalsAny($this->value, $strings);
    }

    /**
     * @param string $needle
     * @param int $offset
     * @return int|null
     */
    public function indexOfFirst(string $needle, int $offset = 0): ?int
    {
        return static::$ref::indexOfFirst($this->value, $needle, $offset);
    }

    /**
     * @param string $needle
     * @param int $offset
     * @return int|null
     */
    public function indexOfLast(string $needle, int $offset = 0): ?int
    {
        return static::$ref::indexOfLast($this->value, $needle, $offset);
    }

    /**
     * @param int $position
     * @param string $insert
     * @return static
     */
    public function insertAt(string $insert, int $position): static
    {
        return new static(static::$ref::insertAt($this->value, $insert, $position));
    }

    /**
     * @param iterable<int|float|string> $replace
     * @param string $delimiterStart
     * @param string $delimiterEnd
     * @return static
     */
    public function interpolate(iterable $replace, string $delimiterStart = '{', string $delimiterEnd = '}'): static
    {
        return new static(static::$ref::interpolate($this->value, $replace, $delimiterStart, $delimiterEnd));
    }

    /**
     * @return bool
     */
    public function isBlank(): bool
    {
        return static::$ref::isBlank($this->value);
    }

    /**
     * @return bool
     */
    public function isNotBlank(): bool
    {
        return static::$ref::isNotBlank($this->value);
    }

    /**
     * @return int
     */
    public function length(): int
    {
        return static::$ref::length($this->value);
    }

    /**
     * @param string $pattern
     * @return array<int, array<string>>
     */
    public function matchAll(string $pattern): array
    {
        return static::$ref::matchAll($this->value, $pattern);
    }

    /**
     * @param string $pattern
     * @return string
     */
    public function matchFirst(string $pattern): string
    {
        return static::$ref::matchFirst($this->value, $pattern);
    }

    /**
     * @param string $pattern
     * @return string|null
     */
    public function matchFirstOrNull(string $pattern): ?string
    {
        return static::$ref::matchFirstOrNull($this->value, $pattern);
    }

    /**
     * @param string $pattern
     * @return string
     */
    public function matchLast(string $pattern): string
    {
        return static::$ref::matchlast($this->value, $pattern);
    }

    /**
     * @param string $pattern
     * @return string|null
     */
    public function matchLastOrNull(string $pattern): ?string
    {
        return static::$ref::matchLastOrNull($this->value, $pattern);
    }

    /**
     * @param int $length
     * @param string $pad
     * @return static
     */
    public function padBoth(int $length, string $pad = ' '): static
    {
        return new static(static::$ref::padBoth($this->value, $length, $pad));
    }

    /**
     * @param int $length
     * @param string $pad
     * @return static
     */
    public function padStart(int $length, string $pad = ' '): static
    {
        return new static(static::$ref::padStart($this->value, $length, $pad));
    }

    /**
     * @param int $length
     * @param string $pad
     * @return static
     */
    public function padEnd(int $length, string $pad = ' '): static
    {
        return new static(static::$ref::padEnd($this->value, $length, $pad));
    }

    /**
     * Passes `$this` to the given callback and returns the result,
     * so it can be used in a chain.
     *
     * @template TPipe
     * @param Closure($this): TPipe $callback
     * Callback which will receive $this as argument.
     * The result of the callback will be returned.
     * @return TPipe
     */
    public function pipe(Closure $callback): mixed
    {
        return $callback($this);
    }

    /**
     * @param string ...$string
     * @return static
     */
    public function prepend(string ...$string): static
    {
        $string[] = $this->value;
        return new static(static::$ref::concat(...$string));
    }

    /**
     * @param int $start
     * @param int $end
     * @return static
     */
    public function range(int $start, int $end): static
    {
        return new static(static::$ref::range($this->value, $start, $end));
    }

    /**
     * @param string $substring
     * @param int|null $limit
     * @param int &$count
     * @return static
     */
    public function remove(string $substring, ?int $limit = null, int &$count = 0): static
    {
        return new static(static::$ref::remove($this->value, $substring, $limit ?? -1, $count));
    }

    /**
     * @param string $substring
     * @return static
     */
    public function removeFirst(string $substring): static
    {
        return new static(static::$ref::removeFirst($this->value, $substring));
    }

    /**
     * @param string $substring
     * @return static
     */
    public function removeLast(string $substring): static
    {
        return new static(static::$ref::removeLast($this->value, $substring));
    }

    /**
     * @param int<0, max> $times
     * @return static
     */
    public function repeat(int $times): static
    {
        return new static(static::$ref::repeat($this->value, $times));
    }

    /**
     * @param string $search
     * @param string $replace
     * @return static
     */
    public function replace(string $search, string $replace): static
    {
        return new static(static::$ref::replace($this->value, $search, $replace));
    }

    /**
     * @param string $search
     * @param string $replace
     * @return static
     */
    public function replaceFirst(string $search, string $replace): static
    {
        return new static(static::$ref::replaceFirst($this->value, $search, $replace));
    }

    /**
     * @param string $search
     * @param string $replace
     * @return static
     */
    public function replaceLast(string $search, string $replace): static
    {
        return new static(static::$ref::replaceLast($this->value, $search, $replace));
    }

    /**
     * @param string $pattern
     * @param string $replacement
     * Replacement for the found pattern.
     * Can be a string or a closure that returns a string.
     * @param int|null $limit
     * @return static
     */
    public function replaceMatch(string $pattern, string $replacement, ?int $limit = null): static
    {
        return new static(static::$ref::replaceMatch($this->value, $pattern, $replacement, $limit));
    }

    /**
     * @param string $pattern
     * @param Closure(array<int|string, string>): string $callback
     * Replacement for the found pattern.
     * Can be a string or a closure that returns a string.
     * @param int|null $limit
     * @return static
     */
    public function replaceMatchWithCallback(string $pattern, Closure $callback, ?int $limit = null): static
    {
        return new static(static::$ref::replaceMatchWithCallback($this->value, $pattern, $callback, $limit));
    }

    /**
     * @return static
     */
    public function reverse(): static
    {
        return new static(static::$ref::reverse($this->value));
    }

    /**
     * @param non-empty-string $separator
     * @param int<0, max>|null $limit
     * @return list<string>
     */
    public function split(string $separator, ?int $limit = null): array
    {
        return static::$ref::split($this->value, $separator, $limit);
    }

    /**
     * @param string $pattern
     * @param int|null $limit
     * @return list<string>
     */
    public function splitMatch(string $pattern, ?int $limit = null): array
    {
        return static::$ref::splitMatch($this->value, $pattern, $limit);
    }

    /**
     * @param string $prefix
     * @return bool
     */
    public function startsWith(string $prefix): bool
    {
        return static::$ref::startsWith($this->value, $prefix);
    }

    /**
     * @param iterable<array-key, string> $prefixes
     * @return bool
     */
    public function startsWithAny(iterable $prefixes): bool
    {
        return static::$ref::startsWithAny($this->value, $prefixes);
    }

    /**
     * @param iterable<array-key, string> $prefixes
     * @return bool
     */
    public function startsWithNone(iterable $prefixes): bool
    {
        return static::$ref::startsWithNone($this->value, $prefixes);
    }

    /**
     * @param int $offset
     * @param int|null $length
     * @return static
     */
    public function substring(int $offset, ?int $length = null): static
    {
        return new static(static::$ref::substring($this->value, $offset, $length));
    }

    /**
     * @param string $search
     * The substring to look for.
     * @return static
     *@see Str::substringAfter()
     *
     */
    public function substringAfter(string $search): static
    {
        return new static(static::$ref::substringAfter($this->value, $search));
    }

    /**
     * @param string $search
     * The substring to look for.
     * @return static
     *@see Str::substringAfterLast()
     *
     */
    public function substringAfterLast(string $search): static
    {
        return new static(static::$ref::substringAfterLast($this->value, $search));
    }

    /**
     * @param string $search
     * The substring to look for.
     * @return static
     *@see Str::substringBefore()
     *
     */
    public function substringBefore(string $search): static
    {
        return new static(static::$ref::substringBefore($this->value, $search));
    }

    /**
     * @param string $search
     * The substring to look for.
     * @return static
     *@see Str::substringBeforeLast()
     *
     */
    public function substringBeforeLast(string $search): static
    {
        return new static(static::$ref::substringBeforeLast($this->value, $search));
    }

    /**
     * @param string $prefix
     * @param string $suffix
     * @return static
     */
    public function surround(string $prefix, string $suffix): static
    {
        return new static(static::$ref::surround($this->value, $prefix, $suffix));
    }

    /**
     * @param int $position
     * @return static
     */
    public function takeFirst(int $position): static
    {
        return new static(static::$ref::takeFirst($this->value, $position));
    }

    /**
     * @param int $position
     * @return static
     */
    public function takeLast(int $position): static
    {
        return new static(static::$ref::takeLast($this->value, $position));
    }

    /**
     * Invokes `$callback` with `$this` as argument and returns `$this`.
     *
     * @param Closure($this): mixed $callback
     * Callback to be invoked.
     * @return $this
     */
    public function tap(Closure $callback): static
    {
        $callback($this);
        return $this;
    }

    /**
     * @return bool
     */
    public function toBool(): bool
    {
        return static::$ref::toBool($this->value);
    }

    /**
     * @return bool|null
     */
    public function toBoolOrNull(): ?bool
    {
        return static::$ref::toBoolOrNull($this->value);
    }

    /**
     * @return static
     */
    public function toCamelCase(): static
    {
        return new static(static::$ref::toCamelCase($this->value));
    }

    /**
     * @return float
     */
    public function toFloat(): float
    {
        return static::$ref::toFloat($this->value);
    }

    /**
     * @return float|null
     */
    public function toFloatOrNull(): ?float
    {
        return static::$ref::toFloatOrNull($this->value);
    }

    /**
     * @return int
     */
    public function toInt(): int
    {
        return static::$ref::toInt($this->value);
    }

    /**
     * @return int|null
     */
    public function toIntOrNull(): ?int
    {
        return static::$ref::toIntOrNull($this->value);
    }

    /**
     * @return static
     */
    public function toKebabCase(): static
    {
        return new static(static::$ref::toKebabCase($this->value));
    }

    /**
     * @return static
     */
    public function toLowerCase(): static
    {
        return new static(static::$ref::toLowerCase($this->value));
    }

    /**
     * @return static
     */
    public function toPascalCase(): static
    {
        $this->value = static::$ref::toPascalCase($this->value);
        return $this;
    }

    /**
     * @return static
     */
    public function toSnakeCase(): static
    {
        return new static(static::$ref::toSnakeCase($this->value));
    }

    /**
     * @return string
     */
    public function toString(): string
    {
        return $this->value;
    }

    /**
     * @return static
     */
    public function toUpperCase(): static
    {
        return new static(static::$ref::toUpperCase($this->value));
    }

    /**
     * @param string|null $characters
     * @return static
     */
    public function trim(?string $characters = null): static
    {
        return new static(static::$ref::trim($this->value, $characters));
    }

    /**
     * @param string|null $characters
     * @return static
     */
    public function trimStart(?string $characters = null): static
    {
        return new static(static::$ref::trimStart($this->value, $characters));
    }

    /**
     * @param string|null $characters
     * @return static
     */
    public function trimEnd(?string $characters = null): static
    {
        return new static(static::$ref::trimEnd($this->value, $characters));
    }
}
