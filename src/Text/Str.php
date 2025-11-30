<?php declare(strict_types=1);

namespace Kirameki\Text;

use Closure;
use Kirameki\Exceptions\InvalidArgumentException;
use Kirameki\Text\Exceptions\NoMatchException;
use Kirameki\Text\Exceptions\ParseException;
use ValueError;
use function abs;
use function array_is_list;
use function array_key_exists;
use function assert;
use function bcdiv;
use function bcmul;
use function bcpow;
use function compact;
use function filter_var;
use function implode;
use function is_numeric;
use function iterator_to_array;
use function ltrim;
use function preg_match;
use function preg_match_all;
use function preg_quote;
use function preg_replace;
use function preg_replace_callback;
use function sprintf;
use function str_contains;
use function str_ends_with;
use function str_pad;
use function str_repeat;
use function str_replace;
use function str_starts_with;
use function strlen;
use function strpos;
use function strrev;
use function strrpos;
use function strtolower;
use function strtoupper;
use function substr;
use function ucwords;
use const FILTER_NULL_ON_FAILURE;
use const FILTER_VALIDATE_INT;
use const INF;
use const NAN;
use const STR_PAD_BOTH;
use const STR_PAD_LEFT;
use const STR_PAD_RIGHT;

/**
 * TODO cut
 * TODO mask
 */
class Str
{
    final public const EMPTY = '';

    public const WHITESPACE = " \t\n\r\0\x0B";

    /**
     * @param string $string
     * @return StrObject
     */
    public static function of(string $string): StrObject
    {
        return StrObject::from($string);
    }

    /**
     * Extract string between the first occurrence of `$from` and `$to`.
     *
     * Example:
     * ```php
     * Str::between('[a] to [b]', '[', ']'); // 'a'
     * Str::between('no tag', '<', '>'); // 'no tag'
     * ```
     *
     * @param string $string
     * The string to look in.
     * @param string $from
     * The starting string to look for.
     * @param string $to
     * The ending string to look for.
     * @return string
     * The extracted part of the string.
     */
    public static function between(string $string, string $from, string $to): string
    {
        if ($from === '') {
            throw new InvalidArgumentException("\$from must not be empty.", compact('string', 'from', 'to'));
        }

        if ($to === '') {
            throw new InvalidArgumentException("\$to must not be empty.", compact('string', 'from', 'to'));
        }

        $startPos = static::indexOfFirst($string, $from);
        if ($startPos === null) {
            return $string;
        }
        $startPos += static::length($from);

        $endPos = static::indexOfFirst($string, $to, $startPos);
        if ($endPos === null) {
            return $string;
        }

        return static::range($string, $startPos, $endPos);
    }

    /**
     * Extract string between the first occurrence of `$from` and last occurrence of `$to`.
     *
     * Example:
     * ```php
     * Str::betweenFurthest('<tag>', '<', '>'); // 'tag'
     * Str::betweenFurthest('no tag', '<', '>'); // 'no tag'
     * ```
     *
     * @param string $string
     * The string to look in.
     * @param string $from
     * The starting string to look for.
     * @param string $to
     * The ending string to look for.
     * @return string
     * The extracted part of the string.
     */
    public static function betweenFurthest(string $string, string $from, string $to): string
    {
        if ($from === '') {
            throw new InvalidArgumentException("\$from must not be empty.", compact('string', 'from', 'to'));
        }

        if ($to === '') {
            throw new InvalidArgumentException("\$to must not be empty.", compact('string', 'from', 'to'));
        }

        $startPos = static::indexOfFirst($string, $from);
        if ($startPos === null) {
            return $string;
        }
        $startPos += static::length($from);

        $endPos = static::indexOfLast($string, $to, $startPos);
        if ($endPos === null) {
            return $string;
        }

        return static::range($string, $startPos, $endPos);
    }

    /**
     * Extract string between the last occurrence of `$from` and `$to`.
     *
     * Example:
     * ```php
     * Str::betweenLast('[a] to [b]', '[', ']'); // 'b'
     * Str::betweenLast('no tag', '<', '>'); // 'no tag'
     * ```
     *
     * @param string $string
     * The string to look in.
     * @param string $from
     * The starting string to look for.
     * @param string $to
     * The ending string to look for.
     * @return string
     * The extracted part of the string.
     */
    public static function betweenLast(string $string, string $from, string $to): string
    {
        if ($from === '') {
            throw new InvalidArgumentException("\$from must not be empty.", compact('string', 'from', 'to'));
        }

        if ($to === '') {
            throw new InvalidArgumentException("\$to must not be empty.", compact('string', 'from', 'to'));
        }

        $startPos = static::indexOfLast($string, $from);
        if ($startPos === null) {
            return $string;
        }
        $startPos += static::length($from);

        $endPos = static::indexOfLast($string, $to, $startPos);
        if ($endPos === null) {
            return $string;
        }

        return static::range($string, $startPos, $endPos);
    }

    /**
     * Convert the first character to upper case letter.
     * Works on all multibyte characters that can be capitalized.
     *
     * Example:
     * ```php
     * Str::capitalize('foo bar'); // 'Foo bar'
     * Str::capitalize('éclore'); // 'Éclore'
     * ```
     *
     * @param string $string
     * The string that will be capitalized.
     * @return string
     * The string that was capitalized.
     */
    public static function capitalize(string $string): string
    {
        $firstChar = static::toUpperCase(static::substring($string, 0, 1));
        $otherChars = static::substring($string, 1);
        return $firstChar . $otherChars;
    }

    /**
     * Chunks string into the given size.
     *
     * Example:
     * ```php
     * Str::chunk('abc', 1); // ['a', 'b', 'c']
     * Str::chunk('TestTestTest', 4); // ['Test', 'Test', 'Test']
     * Str::chunk('TestTestTest', 4, 2); // ['Test', 'Test']
     * ```
     *
     * @param string $string
     * String to be chunked.
     * @param int $size
     * Size of each chunk. Must be >= 1.
     * @param int|null $limit
     * Maximum number times to chunk the string.
     * @return list<string>
     */
    public static function chunk(string $string, int $size, ?int $limit = null): array
    {
        if ($size < 1) {
            throw new InvalidArgumentException("Expected: \$size >= 1. Got: {$size}.", [
                'string' => $string,
                'size' => $size,
                'limit' => $limit,
            ]);
        }

        if ($limit !== null && $limit < 0) {
            throw new InvalidArgumentException("Expected: \$limit >= 0. Got: {$limit}.", [
                'string' => $string,
                'size' => $size,
                'limit' => $limit,
            ]);
        }

        $chunk = [];
        $offset = 0;
        $remains = $limit ?? INF;
        while (true) {
            $piece = static::substring($string, $offset, $size);
            if ($piece === static::EMPTY) {
                break;
            }
            $chunk[] = $piece;
            $offset += $size;
            --$remains;

            if ($remains === 0) {
                $piece = static::substring($string, $offset);
                if ($piece !== static::EMPTY) {
                    $chunk[] = $piece;
                }
                break;
            }
        }
        return $chunk;
    }

    /**
     * Concatenate strings into a single string.
     *
     * Example:
     * ```php
     * Str::concat('combine', ' ', 'me'); // 'combine me'
     * ```
     *
     * @param string ...$string
     * Variable number of strings to be concatenated.
     * @return string
     * The string that was concatenated.
     */
    public static function concat(string ...$string): string
    {
        return implode(self::EMPTY, $string);
    }

    /**
     * Determine if a string contains a given substring.
     *
     * Example:
     * ```php
     * Str::contains('Foo bar', 'bar'); // true
     * Str::contains('👨‍👨‍👧‍👧‍', '👨'); // true
     * Str::contains('a', ''); // true
     * Str::contains('', ''); // true
     * ```
     *
     * @param string $string
     * The string to search in.
     * @param string $substring
     * The substring to search for in the `$string`.
     * @return bool
     * Returns **true** if `$substring` is in `$string`, **false** otherwise.
     */
    public static function contains(string $string, string $substring): bool
    {
        return str_contains($string, $substring);
    }

    /**
     * Determine if a string contains all given substrings.
     * Will return **true** if empty iterable is given.
     *
     * Example:
     * ```php
     * Str::containsAll('Foo bar baz', ['foo', 'bar', 'baz']); // true
     * Str::containsAll('ab', ['a', 'b', 'd']); // false
     * ```
     *
     * @param string $string
     * The string to search in.
     * @param iterable<array-key, string> $substrings
     * The substrings to search for in the `$string`.
     * This must contain at least one substring or exception is thrown.
     * @return bool
     * Returns **true** if all strings in `$substrings` are in `$string`, **false** otherwise.
     */
    public static function containsAll(string $string, iterable $substrings): bool
    {
        foreach (iterator_to_array($substrings) as $substring) {
            if (!static::contains($string, $substring)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Determine if string contains any given substrings.
     * Will return **true** if empty iterable is given.
     *
     * Example:
     * ```php
     * Str::containsAny('Foo Bar', ['Foo', 'Baz']); // true
     * Str::containsAny('Foo Bar', ['Baz', 'Baz']); // false
     * ```
     *
     * @param string $string
     * The string to search in.
     * @param iterable<array-key, string> $substrings
     * The substrings to search for in the `$string`.
     * This must contain at least one value or exception is thrown.
     * @return bool
     * Returns true if any strings in `$substrings` are in `$string`, false otherwise.
     */
    public static function containsAny(string $string, iterable $substrings): bool
    {
        $substrings = iterator_to_array($substrings);

        if ($substrings === []) {
            return true;
        }

        foreach ($substrings as $substring) {
            if (static::contains($string, $substring)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Determine if string contains none of the given substrings.
     * Will return **true** if empty iterable is given.
     *
     * Example:
     * ```php
     * Str::containsNone('Foo Bar', ['Baz', 'Baz']); // true
     * Str::containsNone('Foo Bar', ['Foo', 'Baz']); // false
     * ```
     *
     * @param string $string
     * The string to search in.
     * @param iterable<array-key, string> $substrings
     * The substrings to search for in the `$string`.
     * This must contain at least one value or exception is thrown.
     * @return bool
     * Returns **false** if any strings in `$substrings` are in `$string`, **true** otherwise.
     */
    public static function containsNone(string $string, iterable $substrings): bool
    {
        $substrings = iterator_to_array($substrings);

        if ($substrings === []) {
            return true;
        }

        return !static::containsAny($string, $substrings);
    }

    /**
     * Determine if a string contains any matches to given pattern.
     *
     * Example:
     * ```php
     * Str::containsPattern('foo', '/[A-z\d]+/'); // true
     * Str::containsPattern('foo', '/bar/'); // false
     * ```
     *
     * @param string $string
     * The string to look in.
     * @param string $pattern
     * The pattern to search for in the string.
     * @return bool
     * Returns **true** if the pattern matches **false** otherwise.
     */
    public static function containsPattern(string $string, string $pattern): bool
    {
        return ((int) preg_match($pattern, $string)) > 0;
    }

    /**
     * Count the number of substring occurrences.
     * Unlike PHP's `substr_count`, this will count overlapping strings.
     *
     * Warning: empty search string will throw an exception.
     *
     * Example:
     * ```php
     * Str::count('This is a cat', ' is '); // 1
     * Str::count('aaa', 'aa'); // 2
     * Str::count('foo', ''); // Exception: Search string must be non-empty.
     * ```
     *
     * @param string $string
     * The string that will be searched.
     * @param string $substring
     * The substring to search for.
     * @return int
     * Number of substrings that occurred in given string.
     */
    public static function count(string $string, string $substring, bool $overlapping = false): int
    {
        if ($substring === '') {
            throw new InvalidArgumentException("\$substring must not be empty.", compact('string', 'substring'));
        }

        $counter = 0;
        $offset = 0;
        $length = static::length($string);
        $nextOffset = $overlapping ? 1 : static::length($substring);

        while ($offset < $length) {
            $position = static::indexOfFirst($string, $substring, $offset);

            if ($position === null) {
                break;
            }

            ++$counter;
            $offset = $position + $nextOffset;
        }

        return $counter;
    }

    /**
     * Convert the first character to lower case letter.
     * Works on all multibyte characters that can be decapitalized.
     *
     * Example:
     * ```php
     * Str::decapitalize('Foo Bar'); // 'foo Bar'
     * ```
     *
     * @param string $string
     * The string that will be de-capitalized.
     * @return string
     * The string that was de-capitalized.
     */
    public static function decapitalize(string $string): string
    {
        $firstChar = static::toLowerCase(static::substring($string, 0, 1));
        $otherChars = static::substring($string, 1);
        return $firstChar . $otherChars;
    }

    /**
     * Determine if a substring is not contained in a given string.
     *
     * Example:
     * ```php
     * Str::doesNotContains('Foo bar', 'baz'); // true
     * ```
     *
     * @param string $string
     * The string to search in.
     * @param string $substring
     * The substring to search for in the `$string`.
     * @return bool
     * Returns **false** if `$substring` is in `$string`, **true** otherwise.
     */
    public static function doesNotContain(string $string, string $substring): bool
    {
        return !static::contains($string, $substring);
    }

    /**
     * Checks if a string does not end with a given substring(s).
     * `$suffix` can be a string or an iterable list of strings.
     *
     * Example:
     * ```php
     * Str::doesNotEndWith('abc', 'c'); // true
     * Str::doesNotEndWith('abc', ['a', 'b', 'c', 'd']); // false because 'abc' ends with 'c'
     * ```
     *
     * @param string $string
     * The string to look in.
     * @param string $suffix
     * The suffix to search for in `$string`.
     * @return bool
     * Returns **true** if `$string` does not end with `$suffix`, **false** otherwise.
     */
    public static function doesNotEndWith(string $string, string $suffix): bool
    {
        return !static::endsWith($string, $suffix);
    }

    /**
     * Checks if a string does not start with a given prefix(s).
     * `$prefix` can be a string or an iterable list of strings.
     *
     * Example:
     * ```php
     * Str::doesNotStartWith('abc', 'b'); // true
     * Str::doesNotStartWith('abc', 'a'); // false
     * ```
     *
     * @param string $string
     * The string to look in.
     * @param string $prefix
     * The prefix to search for in `$string`.
     * @return bool
     * Returns **true** if `$string` does not start with `$prefix`, **false** otherwise.
     */
    public static function doesNotStartWith(string $string, string $prefix): bool
    {
        return !static::startsWith($string, $prefix);
    }

    /**
     * Return a string with the first n characters removed.
     *
     * Example:
     * ```php
     * Str::dropFirst('framework', 5); // 'work'
     * Str::dropFirst('framework', 100); // ''
     * ```
     *
     * @param string $string
     * The string to look in.
     * @param int $amount
     * Amount of characters to drop.
     * If the given amount is dropped from the front.
     * If the given amount is greater than the string length, an empty string is returned.
     * @return string
     * The extracted part of the string.
     */
    public static function dropFirst(string $string, int $amount): string
    {
        if ($amount < 0) {
            throw new InvalidArgumentException("Expected: \$amount >= 0. Got: {$amount}.", [
                'string' => $string,
                'amount' => $amount,
            ]);
        }

        return static::substring($string, $amount);
    }

    /**
     * Return a string with the last n characters removed.
     *
     * Example:
     * ```php
     * Str::dropLast('framework', 4); // 'frame'
     * Str::dropLast('framework', 100); // ''
     * ```
     *
     * @param string $string
     * The string to look in.
     * @param int $amount
     * Amount of characters to drop from the end of string.
     * If the given amount is greater than the string length, an empty string is returned.
     * @return string
     * The extracted part of the string.
     */
    public static function dropLast(string $string, int $amount): string
    {
        if ($amount < 0) {
            throw new InvalidArgumentException("Expected: \$amount >= 0. Got: {$amount}.", [
                'string' => $string,
                'amount' => $amount,
            ]);
        }

        if ($amount === 0) {
            return $string;
        }

        return static::substring($string, 0, -$amount);
    }

    /**
     * Checks if a string ends with a given suffix(s).
     * `$suffix` can be a string or an iterable list of strings.
     *
     * Example:
     * ```php
     * Str::endsWith('abc', 'c'); // true
     * Str::endsWith('abc', ['a', 'b']); // false
     * ```
     *
     * @param string $string
     * The string to look in.
     * @param string $suffix
     * The suffix to search for in `$string`.
     * @return bool
     * Returns **true** if `$string` ends with `$suffix`, **false** otherwise.
     */
    public static function endsWith(string $string, string $suffix): bool
    {
        return str_ends_with($string, $suffix);
    }

    /**
     * Checks if a string ends with a given suffixes.
     * `$suffix` can be a string or an iterable list of strings.
     *
     * Example:
     * ```php
     * Str::endsWithAny('abc', ['a', 'c']); // true
     * Str::endsWithAny('abc', ['a', 'b']); // false
     * ```
     *
     * @param string $string
     * The string to look in.
     * @param iterable<array-key, string> $suffixes
     * The suffixes to search for in `$string`.
     * @return bool
     * Returns **true** if `$string` ends with `$suffix`, **false** otherwise.
     */
    public static function endsWithAny(string $string, iterable $suffixes): bool
    {
        foreach ($suffixes as $each) {
            if (static::endsWith($string, $each)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Checks if a string does not end with a given substring(s).
     * `$suffix` can be a string or an iterable list of strings.
     *
     * Example:
     * ```php
     * Str::endsWithNone('abc', 'c'); // true
     * Str::endsWithNone('abc', 'x'); // true because 'abc' ends with 'c'
     * ```
     *
     * @param string $string
     * The string to look in.
     * @param iterable<array-key, string> $suffixes
     * The suffixes to search for in `$string`.
     * @return bool
     * Returns **true** if `$string` does not end with `$suffix`, **false** otherwise.
     */
    public static function endsWithNone(string $string, iterable $suffixes): bool
    {
        return !static::endsWithAny($string, $suffixes);
    }

    /**
     * Compares two strings to determine if they are equal.
     *
     * Example:
     * ```php
     * Str::firstIndexOf('abb', 'b'); // 1
     * Str::firstIndexOf('abb', 'b', 2); // 2
     * Str::firstIndexOf('abb', 'b', 3); // null
     * ```
     *
     * @param string $string
     * The string to be compared.
     * @param string $other
     * The other string to be compared.
     * @return bool
     */
    public static function equals(string $string, string $other): bool
    {
        return $string === $other;
    }

    /**
     * Compares a string to a list of strings to determine if they are equal.
     * Will return **true** if at least one string in the list is equal to the given string.
     *
     * @param string $string
     * The string to be compared.
     * @param iterable<array-key, string> $others
     * The other strings to be compared.
     * @return bool
     */
    public static function equalsAny(string $string, iterable $others): bool
    {
        foreach ($others as $other) {
            if ($string === $other) {
                return true;
            }
        }
        return false;
    }

    /**
     * Find position (in grapheme units) of first occurrence of substring in string.
     *
     * Example:
     * ```php
     * Str::firstIndexOf('abb', 'b'); // 1
     * Str::firstIndexOf('abb', 'b', 2); // 2
     * Str::firstIndexOf('abb', 'b', 3); // null
     * ```
     *
     * @param string $string
     * The string to look in.
     * @param string $substring
     * The substring to search for in `$string`.
     * @param int $offset
     * [Optional] This allows you to specify where in `$string` to
     * start searching as an offset in grapheme units. Defaults to **0**.
     * @return int|null
     * Position where the substring was found. **null** if no match was found.
     */
    public static function indexOfFirst(string $string, string $substring, int $offset = 0): ?int
    {
        try {
            $result = strpos($string, $substring, $offset);
            return $result !== false ? $result : null;
        } catch (ValueError $e) {
            if ($e->getMessage() === 'strpos(): Argument #3 ($offset) must be contained in argument #1 ($haystack)') {
                return null;
            }
            // @codeCoverageIgnoreStart
            throw $e;
            // @codeCoverageIgnoreEnd
        }
    }

    /**
     * Find position (in grapheme units) of last occurrence of substring in string.
     *
     * Example:
     * ```php
     * Str::indexOfLast('abb', 'b'); // 2
     * Str::indexOfLast('abb', 'b', 2); // 2
     * Str::indexOfLast('abb', 'b', 3); // null
     * ```
     *
     * @param string $string
     * The string to look in.
     * @param string $substring
     * The substring to search for in `$string`.
     * @param int $offset
     * [Optional] This allows you to specify where in `$string` to
     * start searching as an offset in grapheme units. Defaults to **0**.
     * @return int|null
     * Position where the substring was found. **null** if no match was found.
     */
    public static function indexOfLast(string $string, string $substring, int $offset = 0): ?int
    {
        try {
            $result = strrpos($string, $substring, $offset);
            return $result !== false ? $result : null;
        } catch (ValueError $e) {
            if ($e->getMessage() === 'strrpos(): Argument #3 ($offset) must be contained in argument #1 ($haystack)') {
                return null;
            }
            // @codeCoverageIgnoreStart
            throw $e;
            // @codeCoverageIgnoreEnd
        }
    }

    /**
     * Insert a substring before the given position.
     *
     * Example:
     * ```php
     * Str::insertAt('abc', 'xyz', 0); // 'xyzabc'
     * Str::insertAt('abc', 'xyz', 3); // 'abcxyz'
     * Str::insertAt('abc', 'xyz', -1); // 'abcxyz'
     * ```
     *
     * @param string $string
     * String to be inserted.
     * @param string $inserting
     * String that will be inserted.
     * @param int $position
     * Position where the `$insert` will be inserted.
     * @return string
     * String which contains `$insert` string at `$position`.
     */
    public static function insertAt(string $string, string $inserting, int $position): string
    {
        if ($position < 0) {
            ++$position;
        }

        return
            static::substring($string, 0, $position) .
            $inserting .
            static::substring($string, $position);
    }

    /**
     * @param string $text
     * @param iterable<string, int|float|string> $replace
     * @param string $delimiterStart
     * @param string $delimiterEnd
     * @return string
     */
    public static function interpolate(
        string $text,
        iterable $replace,
        string $delimiterStart = '{',
        string $delimiterEnd = '}',
    ): string
    {
        $replace = iterator_to_array($replace);

        if ($delimiterStart === self::EMPTY || $delimiterEnd === self::EMPTY) {
            throw new InvalidArgumentException("\$delimiterStart and \$delimiterEnd must not be empty.", [
                'text' => $text,
                'replace' => $replace,
                'delimiterStart' => $delimiterStart,
                'delimiterEnd' => $delimiterEnd,
            ]);
        }

        if (count($replace) > 0 && array_is_list($replace)) {
            throw new InvalidArgumentException("Expected \$replace to be a map. List given.", [
                'text' => $text,
                'replace' => $replace,
            ]);
        }

        $start = preg_quote($delimiterStart);
        $end = preg_quote($delimiterEnd);

        $pattern = '/(?<slashes>\\\\*)' . $start . '(?<placeholder>\w+)(:(?<format>[.%$\'\+\-\d\w]+))?' . $end . '/';

        $callback = static function ($m) use ($replace) {
            $slashes = $m['slashes'];
            $placeholder = $m['placeholder'];
            $format = $m['format'] ?? null;

            $notEscaped = strlen($slashes) % 2 === 0;
            $replaceable = array_key_exists($placeholder, $replace);

            if ($notEscaped && $replaceable) {
                $replaced = $replace[$placeholder];
                if ($format !== null) {
                    $replaced = sprintf($format, $replaced);
                }
                return str_replace('\\\\', '\\', $slashes . $replaced);
            }
            return str_replace('\\\\', '\\', $m[0]);
        };

        return preg_replace_callback($pattern, $callback, $text) ?? '';
    }

    /**
     * Determine whether a given string is blank.
     *
     * Example:
     * ```php
     * Str::isBlank(''); // true
     * Str::isBlank('0'); // false
     * ```
     *
     * @param string $string
     * **string** or **null** variable to be checked.
     * @return bool
     * Returns **true** if variable is an empty string or null. **false** otherwise.
     */
    public static function isBlank(string $string): bool
    {
        return $string === self::EMPTY;
    }

    /**
     * Determine whether a given string is not blank.
     *
     * Example:
     * ```php
     * Str::isNotBlank('a'); // true
     * Str::isNotBlank('0'); // true
     * Str::isNotBlank(''); // false
     * ```
     *
     * @param string $string
     * **string** or **null** variable to be checked.
     * @return bool
     * Returns **false** if variable is empty string or null. **true** otherwise.
     */
    public static function isNotBlank(string $string): bool
    {
        return !static::isBlank($string);
    }

    /**
     * Returns the length of the given string.
     *
     * Example:
     * ```php
     * Str::length(''); // 0
     * Str::length('開発'); // 4
     * Str::length('👨‍👨‍👧‍👦'); // 25
     * ```
     *
     * @param string $string
     * The target input string.
     * @return int
     * The length of the string.
     */
    public static function length(string $string): int
    {
        return strlen($string);
    }

    /**
     * Perform a global regular expression match on given string.
     *
     * Example:
     * ```php
     * Str::matchAll('abcabc', '/a/'); // [['a', 'a']]
     * Str::matchAll('abcabc', '/(?<p1>a)bc/'); // [['abc', 'abc'], 'p1' => ['a', 'a'], ['a', 'a']]
     * ```
     *
     * @param string $string
     * The string to be matched.
     * @param string $pattern
     * The pattern to search for. Must be a valid regex.
     * @return array<int, array<string>>
     * Array of all matches in multidimensional array.
     */
    public static function matchAll(string $string, string $pattern): array
    {
        $match = [];
        preg_match_all($pattern, $string, $match);
        return $match;
    }

    /**
     * Perform a regular expression match on given string and return the first match.
     * Throws a NoMatchException if no match is found.
     *
     * Example:
     * ```php
     * Str::matchFirst('abcabc', '/a/'); // 'a'
     * Str::matchFirst('abcabc', '/z/'); // NoMatchException
     * ```
     *
     * @param string $string
     * The string to be matched.
     * @param string $pattern
     * The pattern to search for. Must be a valid regex.
     * @return string
     */
    public static function matchFirst(string $string, string $pattern): string
    {
        $match = static::matchFirstOrNull($string, $pattern);

        if ($match !== null) {
            return $match;
        }

        throw new NoMatchException("\"{$string}\" does not match {$pattern}", [
            'string' => $string,
            'pattern' => $pattern,
        ]);
    }

    /**
     * Perform a regular expression match on given string and return the first match.
     * Returns **null** if no match is found.
     *
     * Example:
     * ```php
     * Str::matchFirstOrNull('a12c34', '/\d/'); // '12'
     * Str::matchFirstOrNull('abcabc', '/z/'); // null
     * ```
     *
     * @param string $string
     * The string to be matched.
     * @param string $pattern
     * The pattern to search for. Must be a valid regex.
     * @return string|null
     */
    public static function matchFirstOrNull(string $string, string $pattern): ?string
    {
        $match = [];
        preg_match($pattern, $string, $match);
        return $match[0] ?? null;
    }

    /**
     * Perform a regular expression match on given string and return the last match.
     * Throws a NoMatchException if no match is found.
     *
     * Example:
     * ```php
     * Str::matchLast('12a34a', '/\d+/'); // '34'
     * Str::matchLast('abcabc', '/z/'); // NoMatchException
     * ```
     *
     * @param string $string
     * The string to be matched.
     * @param string $pattern
     * The pattern to search for. Must be a valid regex.
     * @return string
     */
    public static function matchLast(string $string, string $pattern): string
    {
        $match = static::matchLastOrNull($string, $pattern);

        if ($match !== null) {
            return $match;
        }

        throw new NoMatchException("\"{$string}\" does not match {$pattern}", [
            'string' => $string,
            'pattern' => $pattern,
        ]);
    }

    /**
     * Perform a regular expression match on given string and return the last match.
     * Returns **null** if no match is found.
     *
     * Example:
     * ```php
     * Str::matchLastOrNull('12a34a', '/\d/'); // '34'
     * Str::matchLastOrNull('abcabc', '/z/'); // null
     * ```
     *
     * @param string $string
     * The string to be matched.
     * @param string $pattern
     * The pattern to search for. Must be a valid regex.
     * @return string|null
     */
    public static function matchLastOrNull(string $string, string $pattern): ?string
    {
        $match = [];
        preg_match_all($pattern, $string, $match);
        $matched = $match[0] ?? [];
        return $matched[count($matched) - 1] ?? null;
    }

    /**
     * Pad a string to a certain length with another string.
     *
     * Example:
     * ```php
     * Str::pad('a', 3, '_'); // 'a__'
     * Str::pad('a', 3, '_', STR_PAD_LEFT); // '__a'
     * Str::pad('a', 3, '_', STR_PAD_BOTH); // '_a_'
     * ```
     *
     * @param string $string
     * The string to be padded.
     * @param int $length
     * The length of the string once it has been padded.
     * If the value is lower than the length of `$string`, the current string will be returned as-is.
     * @param string $padding
     * [Optional] The string used for padding.
     * @param int $type
     * [Optional] The padding type. Type can be STR_PAD_RIGHT, STR_PAD_LEFT, STR_PAD_BOTH. Defaults to **STR_PAD_RIGHT**
     * @return string
     * The padded string.
     */
    public static function pad(string $string, int $length, string $padding = ' ', int $type = STR_PAD_RIGHT): string
    {
        return match ($type) {
            STR_PAD_LEFT,
            STR_PAD_RIGHT,
            STR_PAD_BOTH => str_pad($string, $length, $padding, $type),
            default => throw new InvalidArgumentException("Unknown padding type: {$type}.", [
                'string' => $string,
                'length' => $length,
                'padding' => $padding,
                'type' => $type,
            ]),
        };
    }

    /**
     * Pad a string on both ends to a certain length with another string.
     *
     * Example:
     * ```php
     * Str::padBoth('a', 6, '_'); // '__a___'
     * Str::padBoth('hello', 10, '123'); // '12hello123'
     * ```
     *
     * @param string $string
     * The string to be padded.
     * @param int $length
     * The length of the string once it has been padded.
     * If the value is lower than the length, the current string will be returned as-is.
     * @param string $padding
     * The string used for padding. Defaults to **' '**.
     * @return string
     * The padded string.
     */
    public static function padBoth(string $string, int $length, string $padding = ' '): string
    {
        return static::pad($string, $length, $padding, STR_PAD_BOTH);
    }

    /**
     * Pad string on the beginning of `$string` to a certain length.
     * Padding will be repeated until it the given length is reached.
     * Any part of the padding that overflows will be cut.
     *
     * Example:
     * ```php
     * Str::padEnd('a', 3, '_'); // 'a__'
     * Str::padEnd('a', 3, 'bcde'); // 'abcde'
     * ```
     *
     * @param string $string
     * The string to be padded.
     * @param int $length
     * The length of the string once it has been padded.
     * If the value is lower than the length, the current string will be returned as-is.
     * @param string $padding
     * [Optional] The string used for padding. Defaults to **' '**.
     * @return string
     * The padded string.
     */
    public static function padEnd(string $string, int $length, string $padding = ' '): string
    {
        return static::pad($string, $length, $padding);
    }

    /**
     * Pad string on the beginning of `$string` to a certain length.
     * Padding will be repeated until it the given length is reached.
     * Any part of the padding that overflows will be cut.
     *
     * Example:
     * ```php
     * Str::padStart('a', 3, '_'); // '__a'
     * Str::padStart('a', 3, '123'); // '12a'
     * ```
     *
     * @param string $string
     * The string to be padded.
     * @param int $length
     * The length of the string once it has been padded.
     * If the value is lower than the length, the current string will be returned as-is.
     * @param string $padding
     * [Optional] The string used for padding. Defaults to **' '**.
     * @return string
     * The padded string.
     */
    public static function padStart(string $string, int $length, string $padding = ' '): string
    {
        return static::pad($string, $length, $padding, STR_PAD_LEFT);
    }

    /**
     * Returns a substring of given string defined by starting and ending index.
     * The starting index must be bigger or equal to the ending index.
     *
     * @param string $string
     * The string to be range of.
     * @param int $start
     * The starting index of substring.
     * @param int $end
     * The ending index of substring.
     * @return string
     * The substring of given range.
     */
    public static function range(string $string, int $start, int $end): string
    {
        if ($start === $end) {
            return self::EMPTY;
        }

        $length = static::length($string);

        $_start = $start < 0
            ? $length + $start
            : $start;

        $_end = $end < 0
            ? $length + $end
            : $end;

        if ($_start > $_end) {
            throw new InvalidArgumentException("\$end: {$end} cannot be > \$start: {$start}.", [
                'string' => $string,
                'start' => $start,
                'end' => $end,
            ]);
        }

        return static::substring($string, $_start, -($length - $_end));
    }

    /**
     * Remove all occurrence of `$substring` from `$string`.
     * If `$limit` is set, substring will only be removed from string that many times.
     *
     * Example:
     * ```php
     * Str::remove('aaa', 'a'); // ''
     * Str::remove('me me me', ' ', 1); // 'meme me'
     * ```
     *
     * @param string $string
     * The string to look in.
     * @param string $substring
     * The substring to search for in the `$string`.
     * @param int|null $limit
     * [Optional] Number of times matching string will be removed.
     * If **null** is given, there will be no limit.
     * Defaults to **null**.
     * @param int &$count
     * [Optional][Reference] Sets the number of times replacements occurred.
     * Any value previously set will be reset.
     * @return string
     * Returns string with `$substring` removed.
     */
    public static function remove(string $string, string $substring, ?int $limit = null, int &$count = 0): string
    {
        return static::replace($string, $substring, self::EMPTY, $limit, $count);
    }

    /**
     * Remove the first occurrence of `$substring` from `$string`.
     *
     * Example:
     * ```php
     * Str::removeFirst('abac', 'a'); // 'bac'
     * ```
     *
     * @param string $string
     * The string to look in.
     * @param string $substring
     * The substring to search for in the `$string`.
     * @return string
     * Returns string with `$substring` removed.
     */
    public static function removeFirst(string $string, string $substring): string
    {
        return static::replaceFirst($string, $substring, self::EMPTY);
    }

    /**
     * Remove the first occurrence of `$substring` from `$string`.
     *
     * Example:
     * ```php
     * Str::removeLast('abac', 'a'); // 'abc'
     * ```
     *
     * @param string $string
     * The string to look in.
     * @param string $substring
     * The substring to search for in the `$string`.
     * @return string
     * Returns string with `$substring` removed.
     */
    public static function removeLast(string $string, string $substring): string
    {
        return static::replaceLast($string, $substring, self::EMPTY);
    }

    /**
     * Repeat the given string n times.
     *
     * Example:
     * ```php
     * Str::repeat('a', 3); // 'aaa'
     * Str::repeat('abc', 2); // 'abcabc'
     * ```
     *
     * @param string $string
     * The string to be repeated.
     * @param int $times
     * Number of times to repeat `$string`. Must be >= 0.
     * @return string
     * The repeated string.
     */
    public static function repeat(string $string, int $times): string
    {
        if ($times < 0) {
            throw new InvalidArgumentException("Expected: \$times >= 0. Got: {$times}.", [
                'string' => $string,
                'times' => $times,
            ]);
        }
        return str_repeat($string, $times);
    }

    /**
     * Replace occurrences of the search string with the replacement string.
     *
     * Example:
     * ```php
     * Str::replace('bb', 'b', 'a'); // 'aa'
     * Str::replace('aaa', 'a', '', 2); // 'a'
     * ```
     *
     * @param string $string
     * The string to be replaced.
     * @param string $search
     * The string to replace.
     * @param string $replacement
     * Replacement for the found string.
     * @param int|null $limit
     * [Optional] The maximum times a replacement occurs.
     * Unlimited, if **null** is given.
     * Defaults to **null**.
     * @param int &$count
     * [Optional][Reference] Sets the number of times replacements occurred.
     * Any value previously set will be reset.
     * @return string
     * String with the replaced values.
     */
    public static function replace(
        string $string,
        string $search,
        string $replacement,
        ?int $limit = null,
        int &$count = 0,
    ): string
    {
        if ($limit !== null && $limit < 0) {
            throw new InvalidArgumentException("Expected: \$limit >= 0. Got: {$limit}.", [
                'string' => $string,
                'search' => $search,
                'replacement' => $replacement,
                'limit' => $limit,
            ]);
        }

        $count = 0;

        if ($search === self::EMPTY) {
            return $string;
        }

        $replaced = $string;
        $searchLength = static::length($search);
        $replacementLength = static::length($replacement);
        $offset = 0;
        $max = $limit ?? INF;
        while (($position = static::indexOfFirst($replaced, $search, $offset)) !== null && $max > $count) {
            $before = static::substring($replaced, 0, $position);
            $after = static::substring($replaced, $position + $searchLength);
            $replaced = $before . $replacement . $after;
            $offset = $position + $replacementLength;
            ++$count;
        }

        return $replaced;
    }

    /**
     * Replace the first occurrence of the search string with the replacement string.
     *
     * Example:
     * ```php
     * Str::replaceFirst('bbb', 'b', 'a'); // 'abb'
     * ```
     *
     * @param string $string
     * The string to be replaced.
     * @param string $search
     * The substring to search for.
     * @param string $replacement
     * Replacement for the found substring.
     * @param bool &$replaced
     * [Optional][Reference] Set to **true** if string was replaced, **false** otherwise.
     * @return string
     * String with the replaced values.
     */
    public static function replaceFirst(
        string $string,
        string $search,
        string $replacement,
        bool &$replaced = false,
    ): string
    {
        $count = 0;
        $newString = static::replace($string, $search, $replacement, 1, $count);
        $replaced = $count > 0;
        return $newString;
    }

    /**
     * Replace the last occurrence of the search string with the replacement string.
     *
     * Example:
     * ```php
     * Str::replaceLast('bbb', 'b', 'a'); // 'bba'
     * ```
     *
     * @param string $string
     * The string to be replaced.
     * @param string $search
     * The substring to search for.
     * @param string $replacement
     * Replacement for the found substring.
     * @param bool &$replaced
     * [Optional][Reference] Set to **true** if string was replaced, **false** otherwise.
     * @return string
     * String with the replaced values.
     */
    public static function replaceLast(
        string $string,
        string $search,
        string $replacement,
        bool &$replaced = false,
    ): string
    {
        $replaced = false;

        if ($search === self::EMPTY) {
            return $string;
        }

        $position = static::indexOfLast($string, $search);

        if ($position === null) {
            return $string;
        }

        $before = static::substring($string, 0, $position);
        $after = static::substring($string, $position + static::length($search));
        $replaced = true;

        return $before . $replacement . $after;
    }

    /**
     * Replace substring that match the pattern with the replacement string.
     *
     * Example:
     * ```php
     * Str::replaceMatch('abcc', '/c/', 'b'); // 'abbb'
     * Str::replaceMatch('abcde', '/[A-Za-z]+/', 'x'); // 'x'
     * ```
     *
     * @param string $string
     * The string to be matched and replaced.
     * @param string $pattern
     * The pattern to search for. Must be a valid regex.
     * @param string $replacement
     * Replacement for the found pattern.
     * @param int|null $limit
     * [Optional] The maximum possible replacements for each pattern.
     * Unlimited, if **null** is given.
     * Defaults to **null**.
     * @param int &$count
     * [Optional][Reference] Sets the number of times replacements occurred.
     * Any value previously set will be reset.
     * @return string
     * String with the replaced values.
     */
    public static function replaceMatch(
        string $string,
        string $pattern,
        string $replacement,
        ?int $limit = null,
        int &$count = 0,
    ): string
    {
        if ($limit !== null && $limit < 0) {
            throw new InvalidArgumentException("Expected: \$limit >= 0. Got: {$limit}.", [
                'string' => $string,
                'pattern' => $pattern,
                'replacement' => $replacement,
                'limit' => $limit,
            ]);
        }

        if ($string === self::EMPTY) {
            return $string;
        }

        $count = 0;

        return (string) preg_replace($pattern, $replacement, $string, $limit ?? -1, $count);
    }

    /**
     * Replace substring that match the pattern with the replacement string.
     *
     * Example:
     * ```php
     * Str::replaceMatchWithCallback('abc', '/[ac]/', fn() => 'b'); // 'bbb'
     * ```
     *
     * @param string $string
     * The string to be matched and replaced.
     * @param string $pattern
     * The pattern to search for. Must be a valid regex.
     * @param Closure(array<int|string, string>): string $callback
     * Replacement for the found pattern.
     * Must be a closure that returns a string.
     * @param int|null $limit
     * [Optional] The maximum possible replacements for each pattern.
     * Unlimited, if **null** is given.
     * Defaults to **null**.
     * @param int &$count
     * [Optional][Reference] Sets the number of times replacements occurred.
     * Any value previously set will be reset.
     * @return string
     * String with the replaced values.
     */
    public static function replaceMatchWithCallback(
        string $string,
        string $pattern,
        Closure $callback,
        ?int $limit = null,
        int &$count = 0,
    ): string
    {
        if ($limit !== null && $limit < 0) {
            throw new InvalidArgumentException("Expected: \$limit >= 0. Got: {$limit}.", [
                'string' => $string,
                'pattern' => $pattern,
                'callback' => $callback,
                'limit' => $limit,
            ]);
        }

        if ($string === self::EMPTY) {
            return $string;
        }

        $count = 0;

        return (string) preg_replace_callback($pattern, $callback, $string, $limit ?? -1, $count);
    }

    /**
     * Reverse a string (single byte).
     *
     * Example:
     * ```php
     * Str::reverse('Foo'); // 'ooF'
     * ```
     *
     * @param string $string
     * The string to be reversed.
     * @return string
     * Reversed string.
     */
    public static function reverse(string $string): string
    {
        return strrev($string);
    }

    /**
     * Split a string into an array using a given string.
     *
     * Example:
     * ```php
     * Str::split('abcbd', 'b'); // ['a', 'c', 'd']
     * Str::split('abcbd', 'b', 2); // ['a', 'cbd']
     * ```
     *
     * @param string $string
     * The string to be split.
     * @param string $separator
     * The boundary string(s) used to split.
     * @param int|null $limit
     * [Optional] Maximum number of chunks. Defaults to **null**.
     * @return list<string>
     * Returns an array of strings created by splitting.
     */
    public static function split(string $string, string $separator, ?int $limit = null): array
    {
        if ($limit !== null && $limit < 0) {
            throw new InvalidArgumentException("Expected: \$limit >= 0. Got: {$limit}.", [
                'string' => $string,
                'separator' => $separator,
                'limit' => $limit,
            ]);
        }

        $splits = [];
        $offset = 0;
        $separatorLength = static::length($separator);

        // empty separator will split each character
        if ($separatorLength === 0) {
            return static::chunk($string, 1, $limit);
        }

        $remains = $limit ?? INF;
        while (($pos = static::indexOfFirst($string, $separator, $offset)) !== null && (--$remains) > 0) {
            $splits[] = static::substring($string, $offset, $pos - $offset);
            $offset = $pos + $separatorLength;
        }

        // add remains
        $splits[] = static::substring($string, $offset);

        return $splits;
    }

    /**
     * @param string $string
     * @param string $pattern
     * @param int|null $limit
     * @return list<string>
     */
    public static function splitMatch(string $string, string $pattern, ?int $limit = null): array
    {
        if ($limit !== null && $limit < 0) {
            throw new InvalidArgumentException("Expected: \$limit >= 0. Got: {$limit}.", [
                'string' => $string,
                'pattern' => $pattern,
                'limit' => $limit,
            ]);
        }

        $splits = preg_split($pattern, $string, $limit ?? -1);
        assert(is_array($splits));
        return $splits;
    }

    /**
     * Checks if a string starts with a given substring(s).
     * `$prefix` can be a string or an iterable list of strings.
     *
     * Example:
     * ```php
     * Str::startsWith('abc', 'a'); // true
     * Str::startsWith('abc', 'b'); // false
     * ```
     *
     * @param string $string
     * The string to look in.
     * @param string $prefix
     * The substring(s) to search for in `$string`.
     * @return bool
     * Returns **true** if `$string` starts with `$prefix`, **false** otherwise.
     */
    public static function startsWith(string $string, string $prefix): bool
    {
        return str_starts_with($string, $prefix);
    }

    /**
     * Checks if a string starts with a given substring(s).
     * `$prefix` can be a string or an iterable list of strings.
     *
     * Example:
     * ```php
     * Str::startsWithAny('abc', ['a', 'c']); // true
     * Str::startsWithAny('abc', ['b', 'c']); // false
     * ```
     *
     * @param string $string
     * The string to look in.
     * @param iterable<array-key, string> $prefixes
     * The substrings to search for in `$string`.
     * @return bool
     * Returns **true** if `$string` starts with `$prefix`, **false** otherwise.
     */
    public static function startsWithAny(string $string, iterable $prefixes): bool
    {
        foreach ($prefixes as $each) {
            if (static::startsWith($string, $each)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Checks if a string does not start with a given prefix(s).
     * `$prefix` can be a string or an iterable list of strings.
     *
     * Example:
     * ```php
     * Str::startsWithNone('abc', ['a', 'c', 'd']); // false because 'abc' starts with 'a'
     * Str::startsWithNone('abc', ['x', 'y', 'z']); // true
     * ```
     *
     * @param string $string
     * The string to look in.
     * @param iterable<array-key, string> $prefixes
     * The prefixes to search for in `$string`.
     * @return bool
     * Returns **true** if `$string` does not start with `$prefix`, **false** otherwise.
     */
    public static function startsWithNone(string $string, iterable $prefixes): bool
    {
        return !static::startsWithAny($string, $prefixes);
    }

    /**
     * Return a subset of `$string` starting at `$offset`.
     *
     * Example:
     * ```php
     * Str::substring('abc', 1); // 'a'
     * Str::substring('abc', 0, 1); // 'a'
     * Str::substring('abc', 1, 2); // 'bc'
     * Str::substring('a', 1); // ''
     * ```
     *
     * @param string $string
     * The input string to be sliced.
     * @param int $offset
     * Starting position. When negative, it will count from the end of the string.
     * @param int|null $length
     * Length of string from `$offset`. When negative, that many charts will be omitted from the end of string.
     * Defaults to **null**.
     * @return string
     * The extracted part of string.
     */
    public static function substring(string $string, int $offset, ?int $length = null): string
    {
        return substr($string, $offset, $length);
    }

    /**
     * Extract string after the specified substring.
     * Returns the original string if substring is not found.
     *
     * Example:
     * ```php
     * Str::substringAfter('buffer', 'f'); // 'fer'
     * Str::substringAfter('abc', '_'); // 'abc'
     * ```
     *
     * @param string $string
     * The string to look in.
     * @param string $substring
     * The substring to look for.
     * @return string
     * The extracted part of the string.
     */
    public static function substringAfter(string $string, string $substring): string
    {
        if ($substring === self::EMPTY) {
            return $string;
        }

        $position = static::indexOfFirst($string, $substring);

        return $position !== null
            ? static::substring($string, $position + static::length($substring))
            : $string;
    }

    /**
     * Extract string after the last occurrence of the specified substring.
     * Returns the original string if substring is not found.
     *
     * Example:
     * ```php
     * Str::substringAfterLast('buffer', 'f'); // 'er'
     * Str::substringAfterLast('abc', '_'); // 'abc'
     * ```
     *
     * @param string $string
     * The string to look in.
     * @param string $substring
     * The substring to look for.
     * If no match is found, the entire `$string` is returned.
     * @return string
     * The extracted part of the string.
     */
    public static function substringAfterLast(string $string, string $substring): string
    {
        if ($substring === self::EMPTY) {
            return $string;
        }

        $position = static::indexOfLast($string, $substring);

        return $position !== null
            ? static::substring($string, $position + static::length($substring))
            : $string;
    }

    /**
     * Extract string before the specified substring.
     * Returns the original string if substring is not found.
     *
     * Example:
     * ```php
     * Str::substringBefore('buffer', 'f'); // 'bu'
     * Str::substringBefore('abc', '_'); // 'abc'
     * ```
     *
     * @param string $string
     * The string to look in.
     * @param string $substring
     * The substring to look for.
     * @return string
     * The extracted part of the string.
     */
    public static function substringBefore(string $string, string $substring): string
    {
        if ($substring === self::EMPTY) {
            return $string;
        }

        $position = static::indexOfFirst($string, $substring);

        return $position !== null
            ? static::substring($string, 0, $position)
            : $string;
    }

    /**
     * Extract string before the last occurrence of the specified substring.
     * Original string is returned if substring is not found.
     *
     * Example:
     * ```php
     * Str::substringBeforeLast('buffer', 'f'); // 'buf'
     * Str::substringBeforeLast('abc', '_'); // 'abc'
     * ```
     *
     * @param string $string
     * The string to look in.
     * @param string $substring
     * The substring to look for.
     * @return string
     * The extracted part of the string.
     */
    public static function substringBeforeLast(string $string, string $substring): string
    {
        // If empty string is searched, return the string as is since there is nothing to search.
        if ($substring === self::EMPTY) {
            return $string;
        }

        $position = static::indexOfLast($string, $substring);

        return $position !== null
            ? static::substring($string, 0, $position)
            : $string;
    }

    /**
     * Surround given string with `$before` and `$after`.
     *
     * Example:
     * ```php
     * Str::surround('foo', '"', '"'); // "foo"
     * Str::surround('bar', '[', ']'); // [bar]
     * ```
     *
     * @param string $string
     * String to be wrapped.
     * @param string $before
     * String that will be prepended.
     * @param string $after
     * String that will be appended.
     * @return string
     */
    public static function surround(string $string, string $before, string $after): string
    {
        return static::concat($before, $string, $after);
    }

    /**
     * Return a string which only contain the first n characters.
     *
     * Example:
     * ```php
     * Str::takeFirst('framework', 5); // 'frame'
     * Str::takeFirst('framework', 100); // 'framework'
     * ```
     *
     * @param string $string
     * The string to look in.
     * @param int $amount
     * Amount of characters to take from the front.
     * If the given amount is greater than the string length, the given string is returned.
     * @return string
     * The extracted part of the string.
     */
    public static function takeFirst(string $string, int $amount): string
    {
        if ($amount < 0) {
            throw new InvalidArgumentException("Expected: \$amount >= 0. Got: {$amount}.", [
                'string' => $string,
                'amount' => $amount,
            ]);
        }

        return static::substring($string, 0, $amount);
    }

    /**
     * Return a string which only contain the last n characters.
     *
     * Example:
     * ```php
     * Str::takeLast('framework', 4); // 'work'
     * Str::takeLast('framework', 100); // 'framework'
     * ```
     *
     * @param string $string
     * The string to look in.
     * @param int $amount
     * Amount of characters to take from the end.
     * If the given amount is greater than the string length, the given string is returned.
     * @return string
     * The extracted part of the string.
     */
    public static function takeLast(string $string, int $amount): string
    {
        if ($amount < 0) {
            throw new InvalidArgumentException("Expected: \$amount >= 0. Got: {$amount}.", [
                'string' => $string,
                'amount' => $amount,
            ]);
        }

        return static::substring($string, -$amount);
    }

    /**
     * Converts the string to boolean value.
     * "1", "true", "TRUE" will be converted to `true`.
     * "0", "false", "FALSE" will be converted to `false`.
     * Any other string will return **null**.
     *
     * Example:
     * ```php
     * Str::toBoolOrNull('1'); // true
     * Str::toBoolOrNull('0'); // false
     * Str::toBoolOrNull(''); // null
     * Str::toBoolOrNull('👨‍👨‍👧‍👦'); // null
     * ```
     *
     * @param string $string
     * The string to be converted.
     * @return bool|null
     * Returns bool if string is compatible, `null` otherwise.
     */
    public static function toBoolOrNull(string $string): bool|null
    {
        // 1 and 0 are supported to accommodate for MySQL input
        return match ($string) {
            'true', 'TRUE', '1' => true,
            'false', 'FALSE', '0' => false,
            default => null,
        };
    }

    /**
     * Converts the string to boolean value.
     * "1", "true", "TRUE" will be converted to `true`.
     * "0", "false", "FALSE" will be converted to `false`.
     * Any other string will throw a RuntimeException.
     *
     * Example:
     * ```php
     * Str::toBool('true'); // true
     * Str::toBool('FALSE'); // false
     * Str::toBool('1'); // true
     * Str::toBool('0'); // false
     * Str::toBool(''); // ParseException: "" is not a valid boolean string.
     * Str::toBool('👨‍👨‍👧‍👦'); // ParseException: "👨‍👨‍👧‍👦" is not a valid boolean string.
     * ```
     *
     * @param string $string
     * The string to be converted.
     * @return bool
     * Returns bool if string is compatible, `null` otherwise.
     */
    public static function toBool(string $string): bool
    {
        $bool = static::toBoolOrNull($string);
        if ($bool === null) {
            throw new ParseException("\"$string\" is not a valid boolean string.");
        }
        return $bool;
    }

    /**
     * Convert string to camel case.
     *
     * Example:
     * ```php
     * Str::toCamelCase('Camel case'); // 'camelCase'
     * Str::toCamelCase('camel-case'); // 'camelCase'
     * Str::toCamelCase('camel_case'); // 'camelCase'
     * ```
     *
     * @param string $string
     * The string to be camel cased.
     * @return string
     * The string converted to camel case.
     */
    public static function toCamelCase(string $string): string
    {
        return static::decapitalize(static::toPascalCase($string));
    }

    /**
     * Convert the string to floating point value.
     * If string contains anything other than a number or precision is lost,
     * a RuntimeException is thrown.
     *
     * Example:
     * ```php
     * Str::toFloat('1.1'); // 1.1
     * Str::toFloat('1'); // 1.0
     * Str::toFloat('0.0'); // 0.0
     * Str::toFloat('-0.0'); // 0.0
     * Str::toFloat(''); // ParseException: "" is not a valid float string.
     * ```
     *
     * @param string $string
     * The string to be converted.
     * @return float
     */
    public static function toFloat(string $string): float
    {
        $precisionLost = false;
        $float = static::toFloatOrNull($string, $precisionLost);
        if ($float === null) {
            $precisionLost
                ? throw new ParseException("Float precision lost for \"$string\"")
                : throw new ParseException("\"{$string}\" is not a valid float.");
        }
        return $float;
    }

    /**
     * Convert the string to floating point value.
     * If string contains anything other than a number or precision is lost, `null` is returned.
     *
     * Example:
     * ```php
     * Str::toFloatOrNull('1.1'); // 1.1
     * Str::toFloatOrNull('1'); // 1.0
     * Str::toFloatOrNull('0.0'); // 0.0
     * Str::toFloatOrNull('-0.0'); // 0.0
     * Str::toFloatOrNull('-1.2e-2'); // -0.012
     * Str::toFloatOrNull(''); // null
     * ```
     *
     * @param string $string
     * The string to be converted.
     * @param bool $precisionLost
     * [Optional][Reference] Reference to be set to **true** if precision is lost.
     * For internal use. Defaults to **false**.
     * @return float|null
     * Returns float if string is compatible, `null` otherwise.
     */
    public static function toFloatOrNull(string $string, bool &$precisionLost = false): float|null
    {
        $formatted = static::matchFloatFormat($string);

        // null means it was probably not a regular float, so check for special chars.
        // If there is still no match, then it's not a float
        if ($formatted === null) {
            return match ($string) {
                'NaN', '-NaN', 'NAN', '-NAN' => NAN,
                'INF', 'Infinity' => INF,
                '-INF', '-Infinity' => -INF,
                default => null,
            };
        }

        $float = (float) $formatted;
        $floatAsString = "{$float}";

        if ($formatted === $floatAsString) {
            return $float;
        }

        // If the strings don't match, it might mean that float was
        // converted to a scientific notation so, convert it back to number
        // and re-check for equality.
        $reformatted = static::matchFloatFormat($floatAsString);
        if ($formatted === $reformatted) {
            return $float;
        }

        // If this is reached, it means there was a precision loss.
        $precisionLost = true;
        return null;
    }

    /**
     * @param string $string
     * @return string|null
     */
    protected static function matchFloatFormat(string $string): ?string
    {
        $match = [];
        // https://www.json.org/img/number.png
        if (preg_match("/^(?<mantissa>-?([1-9][0-9]*|[0-9])(\.(?<dec>[0-9]*))?)([eE](?<sign>[+\-]?)(?<exponent>[0-9]+))?$/", $string, $match)) {
            $mantissa = $match['mantissa'];
            assert(is_numeric($mantissa));
            if (array_key_exists('exponent', $match)) {
                $exponent = $match['exponent'];
                /** @phpstan-ignore offsetAccess.notFound */
                $direction = $match['sign'] ?: '+';
                $power = bcpow('10', $exponent);
                /** @phpstan-ignore offsetAccess.notFound */
                $decimals = strlen($match['dec']);
                $scale = (int)$exponent;
                $mantissa = $direction === '+'
                    ? bcmul($mantissa, $power, abs($scale - $decimals))
                    : bcdiv($mantissa, $power, $scale + $decimals);
            }
            if (!str_contains($mantissa, '.')) {
                $mantissa .= '.0';
            }
            return $mantissa;
        }
        return null;
    }

    /**
     * Convert the string to integer value.
     * If string contains anything other than a number or is larger than PHP_INT_MAX,
     * a RuntimeException will be thrown.
     *
     * Example:
     * ```php
     * Str::toInt('1'); // 1
     * Str::toInt('-1'); // -1
     * Str::toInt('1.0'); // RuntimeException: "1.0" is not a valid integer string.
     * ```
     *
     * @param string $string
     * The string to be converted.
     * @return int
     * Converted integer.
     */
    public static function toInt(string $string): int
    {
        $int = static::toIntOrNull($string);
        if ($int === null) {
            throw new ParseException("\"{$string}\" is not a valid integer.");
        }
        return $int;
    }

    /**
     * Convert the string to integer value.
     * If string contains anything other than a number or is larger than PHP_INT_MAX,
     * **null** is returned.
     *
     * Example:
     * ```php
     * Str::toIntOrNull('1'); // 1
     * Str::toIntOrNull('-1'); // -1
     * Str::toIntOrNull('1.0'); // null
     * Str::toIntOrNull(str_repeat('1', 20)); // null
     * ```
     *
     * @param string $string
     * The string to be converted.
     * @return int|null
     * Returns converted int if string is compatible, `null` otherwise.
     */
    public static function toIntOrNull(string $string): int|null
    {
        if (preg_match("/^-?([1-9][0-9]*|[0-9])$/", $string)) {
            return filter_var($string, FILTER_VALIDATE_INT, FILTER_NULL_ON_FAILURE);
        }
        return null;
    }

    /**
     * Convert the given string to kebab-case.
     *
     * Example:
     * ```php
     * Str::toKebabCase('foo bar'); // 'foo-bar'
     * Str::toKebabCase('foo_bar'); // 'foo-bar'
     * Str::toKebabCase('FooBar'); // 'foo-bar'
     * Str::toKebabCase('HTTPClient'); // 'http-client'
     * ```
     *
     * @param string $string
     * String to be converted to kebab-case.
     * @return string
     * Kebab-cased string.
     */
    public static function toKebabCase(string $string): string
    {
        $converting = (string) preg_replace(['/([a-z\d])([A-Z])/', '/([^-])([A-Z][a-z])/'], '$1-$2', $string);
        $converting = (string) str_replace([' ', '_'], '-', $converting);
        return static::toLowerCase($converting);
    }

    /**
     * Convert the given string to lower case.
     *
     * Example:
     * ```php
     * Str::toLowerCase('AbCd'); // 'abcd'
     * ```
     *
     * @param string $string
     * The string being lower-cased.
     * @return string
     * String with all alphabetic characters converted to lower case.
     */
    public static function toLowerCase(string $string): string
    {
        return strtolower($string);
    }

    /**
     * Convert the given string to pascal-case.
     *
     * Example:
     * ```php
     * Str::toPascalCase('foo bar'); // 'FooBar'
     * Str::toPascalCase('foo-bar'); // 'FooBar'
     * Str::toPascalCase('foo_bar'); // 'FooBar'
     * Str::toPascalCase('FooBar'); // 'FooBar'
     * ```
     *
     * @param string $string
     * String to be converted to pascal-case.
     * @return string
     * Pascal-cased string.
     */
    public static function toPascalCase(string $string): string
    {
        return str_replace(['-', '_', ' '], self::EMPTY, ucwords($string, '-_ '));
    }

    /**
     * Convert the given string to snake-case.
     *
     * Example:
     * ```php
     * Str::toSnakeCase('foo bar'); // 'foo_bar'
     * Str::toSnakeCase('foo_bar'); // 'foo_bar'
     * Str::toSnakeCase('FooBar'); // 'foo_bar'
     * Str::toSnakeCase('HTTPClient'); // 'http_client'
     * ```
     *
     * @param string $string
     * String to be converted to snake-case.
     * @return string
     * Snake-cased string.
     */
    public static function toSnakeCase(string $string): string
    {
        $converting = (string) preg_replace(['/([a-z\d])([A-Z])/', '/([^_])([A-Z][a-z])/'], '$1_$2', $string);
        $converting = (string) str_replace([' ', '-'], '_', $converting);
        return static::toLowerCase($converting);
    }

    /**
     * Convert the given string to upper case.
     *
     * Example:
     * ```php
     * Str::toUpperCase('AbCd'); // 'ABCD'
     * ```
     *
     * @param string $string
     * The string being upper-cased.
     * @return string
     * String with all alphabetic characters converted to upper case.
     */
    public static function toUpperCase(string $string): string
    {
        return strtoupper($string);
    }

    /**
     * Strip whitespace (or other characters) from the beginning and end of a string.
     *
     * Example:
     * ```php
     * Str::trim(' foo bar '); // 'foo bar'
     * Str::trim("\t\rfoo bar\n\r"); // 'foo bar'
     * ```
     *
     * @param string $string
     * The string to be trimmed.
     * @param string|null $characters
     * [Optional] Characters that would be stripped.
     * Defaults to PCRE spaces. (https://www.pcre.org/original/doc/html/pcrepattern.html)
     * @return string
     * The trimmed string.
     */
    public static function trim(string $string, ?string $characters = null): string
    {
        return static::trimEnd(static::trimStart($string, $characters), $characters);
    }

    /**
     * Strip whitespace (or other characters) from the end of a string.
     *
     * Example:
     * ```php
     * Str::trimEnd(' foo bar '); // ' foo bar'
     * Str::trimEnd("\t\rfoo bar\n\r"); // "\t\rfoo bar"
     * ```
     *
     * @param string $string
     * The string to be trimmed.
     * @param string|null $characters
     * [Optional] Characters that would be stripped.
     * Defaults to PCRE spaces. (https://www.pcre.org/original/doc/html/pcrepattern.html)
     * @return string
     * The trimmed string.
     */
    public static function trimEnd(string $string, ?string $characters = null): string
    {
        $characters ??= self::WHITESPACE;

        if ($characters === self::EMPTY) {
            return $string;
        }

        return rtrim($string, $characters);
    }

    /**
     * Strip whitespace (or other characters) from the start of a string.
     *
     * Example:
     * ```php
     * Str::trimStart(' foo bar '); // 'foo bar '
     * Str::trimStart("\t\rfoo bar\n\r"); // "foo bar\n\r"
     * ```
     *
     * @param string $string
     * The string to be trimmed.
     * @param string|null $characters
     * [Optional] Characters that would be stripped.
     * Defaults to PCRE spaces. (https://www.pcre.org/original/doc/html/pcrepattern.html)
     * @return string
     * The trimmed string.
     */
    public static function trimStart(string $string, ?string $characters = null): string
    {
        $characters ??= self::WHITESPACE;

        if ($characters === self::EMPTY) {
            return $string;
        }

        return ltrim($string, $characters);
    }

    /**
     * Prepend string with given prefix if it is not already present.
     *
     * Example:
     * ```php
     * Str::withPrefix('foo', '_'); // "_foo"
     * Str::withPrefix('_foo', '_'); // "_foo"
     * ```
     *
     * @param string $string
     * String to be ensured with the prefix.
     * @param string $prefix
     * Prefix which will be applied if it is not present in `$string`.
     * @return string
     * String with the given prefix.
     */
    public static function withPrefix(string $string, string $prefix): string
    {
        $offset = static::length($prefix);
        if (static::substring($string, 0, $offset) === $prefix) {
            return $string;
        }
        return $prefix . $string;
    }

    /**
     * Append string with given suffix if it is not already present.
     *
     * Example:
     * ```php
     * Str::withSuffix('foo', '_'); // "foo_"
     * Str::withSuffix('foo_', '_'); // "foo_"
     * ```
     *
     * @param string $string
     * String to be ensured with the suffix.
     * @param string $suffix
     * Suffix which will be applied if it is not present in `$string`.
     * @return string
     * String with the given suffix.
     */
    public static function withSuffix(string $string, string $suffix): string
    {
        $offset = static::length($suffix);
        if (static::substring($string, -$offset) === $suffix) {
            return $string;
        }
        return $string . $suffix;
    }
}
