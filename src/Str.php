<?php declare(strict_types=1);

namespace Kirameki\Utils;

use LogicException;
use RuntimeException;
use Webmozart\Assert\Assert;
use function abs;
use function assert;
use function ceil;
use function floor;
use function grapheme_strlen;
use function grapheme_strpos;
use function grapheme_strrpos;
use function grapheme_substr;
use function implode;
use function intl_get_error_message;
use function is_array;
use function iterator_to_array;
use function ltrim;
use function mb_strcut;
use function mb_strlen;
use function mb_strtolower;
use function mb_strtoupper;
use function preg_match;
use function preg_match_all;
use function preg_quote;
use function preg_replace;
use function preg_split;
use function rtrim;
use function str_contains;
use function str_ends_with;
use function str_repeat;
use function str_replace;
use function str_starts_with;
use function strlen;
use function strpos;
use function strrev;
use function strrpos;
use function substr_replace;
use function trim;
use function ucwords;
use function wordwrap;

class Str
{
    public const ENCODING = 'UTF-8';

    /**
     * Extract string after the `$search` string.
     *
     * Example:
     * ```php
     * Str::after('framework', 'frame'); // 'work'
     * ```
     *
     * @param string $string
     * The string to look in. Must be a valid UTF-8 encoded string.
     * @param string $search
     * The string to look for. Must be a valid UTF-8 encoded string.
     * If no match is found, the entire `$string` is returned.
     * @return string
     * The extracted part of the string.
     */
    public static function after(string $string, string $search): string
    {
        $pos = grapheme_strpos($string, $search);

        // If string is not matched, return blank immediately.
        if ($pos === false) {
            return '';
        }

        return static::substring($string, $pos + static::length($search));
    }

    /**
     * Extract string after the specified `$position`.
     *
     * Example:
     * ```php
     * Str::afterIndex('framework', 5); // 'work'
     * Str::afterIndex('framework', -4); // 'work'
     * ```
     *
     * @param string $string
     * The string to look in. Must be a valid UTF-8 encoded string.
     * @param int $position
     * The target position of string.
     * If a negative value is given, it will seek from the end of the string.
     * If the given position is out of bounds, the entire `$string` is returned.
     * @return string
     * The extracted part of the string.
     */
    public static function afterIndex(string $string, int $position): string
    {
        return static::substring($string, $position);
    }

    /**
     * Extract string after the last occurrence of `$search` string.
     *
     * Example:
     * ```php
     * Str::afterLast('Hi! Hi!', 'Hi'); // '!'
     * ```
     *
     * @param string $string
     * The string to look in. Must be a valid UTF-8 encoded string.
     * @param string $search
     * The string to look in. Must be a valid UTF-8 encoded string.
     * If no match is found, the entire `$string` is returned.
     * @return string
     * The extracted part of the string.
     */
    public static function afterLast(string $string, string $search): string
    {
        // If empty string is searched, return the string as is since there is nothing to trim.
        if ($search === '') {
            return $string;
        }

        $pos = grapheme_strrpos($string, $search);

        // If string is not matched, return blank immediately.
        if ($pos === false) {
            return '';
        }

        return static::substring($string, $pos + static::length($search));
    }

    /**
     * Extract string before the `$search` string.
     *
     * Example:
     * ```php
     * Str::before('framework', 'work'); // 'frame'
     * ```
     *
     * @param string $string
     * The string to look in. Must be a valid UTF-8 encoded string.
     * @param string $search
     * The string to look in. Must be a valid UTF-8 encoded string.
     * If no match is found, the entire `$string` is returned.
     * @return string
     * The extracted part of the string.
     */
    public static function before(string $string, string $search): string
    {
        // If empty string is searched, return the string as is since there is nothing to search.
        if ($search === '') {
            return $string;
        }

        $pos = grapheme_strpos($string, $search);

        // If string is not matched, return itself immediately.
        if ($pos === false) {
            return $string;
        }

        return static::substring($string, 0, $pos);
    }

    /**
     * Extract string before the specified `$position`.
     *
     * Example:
     * ```php
     * Str::beforeIndex('framework', 5); // 'frame'
     * Str::beforeIndex('framework', -4); // 'frame'
     * ```
     *
     * @param string $string
     * The string to look in. Must be a valid UTF-8 encoded string.
     * @param int $position
     * The target position of string.
     * If a negative value is given, it will seek from the end of the string.
     * If the given position is out of bounds, the entire `$string` is returned.
     * @return string
     * The extracted part of the string.
     */
    public static function beforeIndex(string $string, int $position): string
    {
        return static::substring($string, 0, $position);
    }

    /**
     * Extract string before the last occurrence of `$search` string.
     *
     * Example:
     * ```php
     * Str::afterLast('Hi! Hi!', 'Hi!'); // 'Hi! '
     * ```
     *
     * @param string $string
     * The string to look in. Must be a valid UTF-8 encoded string.
     * @param string $search
     * The string to look in. Must be a valid UTF-8 encoded string.
     * If no match is found, the entire `$string` is returned.
     * @return string
     * The extracted part of the string.
     */
    public static function beforeLast(string $string, string $search): string
    {
        $pos = grapheme_strrpos($string, $search);

        // If string is not matched, return itself immediately.
        if ($pos === false) {
            return $string;
        }

        return static::substring($string, 0, $pos);
    }

    /**
     * Extract string between the first occurrence of `$from` and last occurrence of `$to`.
     *
     * Example:
     * ```php
     * Str::between('<tag>', '<', '>'); // 'tag'
     * ```
     *
     * @param string $string
     * The string to look in. Must be a valid UTF-8 encoded string.
     * @param string $from
     * The starting string to look for. Must be a valid UTF-8 encoded string.
     * @param string $to
     * The ending string to look for. Must be a valid UTF-8 encoded string.
     * @return string
     * The extracted part of the string.
     */
    public static function between(string $string, string $from, string $to): string
    {
        return static::beforeLast(static::after($string, $from), $to);
    }

    /**
     * Counts the size of bytes for the given string.
     *
     * Example:
     * ```php
     * Str::bytes('あ'); // 3
     * Str::bytes('👨‍👨‍👧‍👦'); // 25
     * ```
     *
     * @param string $string
     * The target string being counted.
     * @return int
     * The byte size of the given string.
     */
    public static function bytes(string $string): int
    {
        return strlen($string);
    }

    /**
     * Convert string to camel case.
     *
     * Example:
     * ```php
     * Str::camelCase('Camel case'); // 'camelCase'
     * Str::camelCase('camel-case'); // 'camelCase'
     * Str::camelCase('camel_case'); // 'camelCase'
     * ```
     *
     * @param string $string
     * The string to be camel cased.
     * @return string
     * The string converted to camel case.
     */
    public static function camelCase(string $string): string
    {
        return static::decapitalize(static::pascalCase($string));
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
     * The string that will be capitalized. Must be a valid UTF-8 encoded string.
     * @return string
     * The string that was capitalized.
     */
    public static function capitalize(string $string): string
    {
        $firstChar = static::toUpper(static::substring($string, 0, 1));
        $otherChars = static::substring($string, 1);
        return $firstChar . $otherChars;
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
        return implode('', $string);
    }

    /**
     * Determine if a string contains a given substring.
     *
     * Example:
     * ```php
     * Str::contains('Foo bar', 'bar'); // true
     * ```
     *
     * @param string $haystack
     * The string to search in.
     * @param string $needle
     * The substring to search for in the `$haystack`.
     * @return bool
     * Returns **true** if `$needle` is in `$haystack`, **false** otherwise.
     */
    public static function contains(string $haystack, string $needle): bool
    {
        return str_contains($haystack, $needle);
    }

    /**
     * Determine if a string contains all given substrings.
     *
     * Example:
     * ```php
     * Str::contains('Foo bar baz', ['foo', 'bar', 'baz']); // true
     * Str::contains('ab', ['a', 'b', 'd']); // false
     * ```
     *
     * @param string $haystack
     * The string to search in.
     * @param iterable<array-key, string> $needles
     * The substrings to search for in the `$haystack`.
     * This must contain at least one needle or exception is thrown.
     * @return bool
     * Returns **true** if all strings in `$needles` are in `$haystack`, **false** otherwise.
     */
    public static function containsAll(string $haystack, iterable $needles): bool
    {
        $needles = is_array($needles) ? $needles : iterator_to_array($needles);

        Assert::minCount($needles, 1);

        foreach ($needles as $needle) {
            if(!str_contains($haystack, $needle)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Determine if string contains any given substrings.
     *
     * Example:
     * ```php
     * Str::contains('Foo Bar', ['Foo', 'Baz']); // true
     * Str::contains('Foo Bar', ['Baz', 'Baz']); // false
     * ```
     *
     * @param string $haystack
     * The string to search in.
     * @param iterable<array-key, string> $needles
     * The substrings to search for in the `$haystack`.
     * This must contain at least one needle or exception is thrown.
     * @return bool
     * Returns true if any strings in `$needles` are in `$haystack`, false otherwise.
     */
    public static function containsAny(string $haystack, iterable $needles): bool
    {
        $needles = is_array($needles) ? $needles : iterator_to_array($needles);

        Assert::minCount($needles, 1);

        foreach ($needles as $needle) {
            if(str_contains($haystack, $needle)) {
                return true;
            }
        }

        return false;
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
     * The string to look in. Must be valid a UTF-8 encoded string.
     * @param string $pattern
     * The pattern to search for in the string.
     * @return bool
     * Returns **true** if the pattern matches **false** otherwise.
     */
    public static function containsPattern(string $string, string $pattern): bool
    {
        $result = preg_match($pattern, $string);

        Assert::integer($result);

        return $result > 0;
    }

    /**
     * Extracts the substring from string based on the given position.
     * The position is determined by bytes.
     * If the position happens to be between a multibyte character, the cut is performed on the entire character.
     * Grapheme string is not supported for this method.
     *
     * Example:
     * ```php
     * Str::cut('abc', 1); // 'a'
     * Str::cut('あいう', 1); // '' since あ is 3 bytes long.
     * ```
     *
     * @param string $string
     * The string to look in. Must be a valid UTF-8 encoded string.
     * @param int $position
     * The position where the string will be cut.
     * @param string $ellipsis
     * An ellipsis which will be appended to the cut string if string is greater than cut string. Defaults to **''**.
     * @return string
     */
    public static function cut(string $string, int $position, string $ellipsis = ''): string
    {
        $cut = mb_strcut($string, 0, $position, self::ENCODING);
        if ($ellipsis !== '' && mb_strlen($cut) < mb_strlen($string)) {
            $cut .= $ellipsis;
        }
        return $cut;
    }

    /**
     * Convert the first character to lower case letter.
     * Works on all multibyte characters that can be decapitalized.
     *
     * Example:
     * ```php
     * Str::decapitalize('Foo Bar'); // 'foo Bar'
     * Str::decapitalize('Éclore'); // 'éclore'
     * ```
     *
     * @param string $string
     * The string that will be decapitalized. Must be a valid UTF-8 encoded string.
     * @return string
     * The string that was decapitalized.
     */
    public static function decapitalize(string $string): string
    {
        $firstChar = static::toLower(static::substring($string, 0, 1));
        $otherChars = static::substring($string, 1);
        return $firstChar . $otherChars;
    }

    /**
     * Delete a substring from a given string.
     * If `$limit` is set, substring will only be removed from string that many times.
     *
     * Example:
     * ```php
     * Str::delete('aaa', 'a'); // ''
     * Str::delete('me me me me', ' ', 2); // 'mememe'
     * ```
     *
     * @param string $haystack
     * The string to look in.
     * @param string $needle
     * The substring to search for in the `$haystack`.
     * @param int|null $limit
     * Number of times matching string will be removed. If **null** is given, there will be no limit. Defaults to **null**.
     * @return string
     * Returns string with `$needle` removed.
     */
    public static function delete(string $haystack, string $needle, ?int $limit = null): string
    {
        return static::replace($haystack, $needle, '', $limit);
    }

    /**
     * Checks if a string does not end with a given substring(s).
     * `$needle` can be a string or an iterable list of strings.
     *
     * Example:
     * ```php
     * Str::doesNotEndWith('abc', 'c'); // true
     * Str::doesNotEndWith('abc', ['a', 'b', 'c', 'd']); // false because 'abc' ends with 'c'
     * ```
     *
     * @param string $haystack
     * The string to look in.
     * @param string|iterable<array-key, string> $needle
     * The substring(s) to search for in the haystack.
     * @return bool
     * Returns **true** if `$haystack` does not end with `$needle`, **false** otherwise.
     */
    public static function doesNotEndWith(string $haystack, string|iterable $needle): bool
    {
        return !static::endsWith($haystack, $needle);
    }

    /**
     * Checks if a string does not start with a given substring(s).
     * `$needle` can be a string or an iterable list of strings.
     *
     * Example:
     * ```php
     * Str::doesNotStartWith('abc', 'b'); // true
     * Str::doesNotStartWith('abc', ['a', 'c', 'd']); // false because 'abc' starts with 'a'
     * ```
     *
     * @param string $haystack
     * The string to look in.
     * @param string|iterable<array-key, string> $needle
     * The substring(s) to search for in the haystack.
     * @return bool
     * Returns **true** if `$haystack` does not start with `$needle`, **false** otherwise.
     */
    public static function doesNotStartWith(string $haystack, string|iterable $needle): bool
    {
        return !static::startsWith($haystack, $needle);
    }

    /**
     * Checks if a string ends with a given substring(s).
     * `$needle` can be a string or an iterable list of strings.
     *
     * Example:
     * ```php
     * Str::endsWith('abc', 'c'); // true
     * Str::endsWith('abc', ['a', 'b']); // false
     * ```
     *
     * @param string $haystack
     * The string to look in.
     * @param string|iterable<array-key, string> $needle
     * The substring(s) to search for in the haystack.
     * @return bool
     * Returns **true** if `$haystack` ends with `$needle`, **false** otherwise.
     */
    public static function endsWith(string $haystack, string|iterable $needle): bool
    {
        $needles = is_iterable($needle) ? $needle : [$needle];
        foreach ($needles as $each) {
            if (str_ends_with($haystack, $each)) {
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
     * @param string $haystack
     * The string to look in.
     * @param string $needle
     * The substring to search for in the haystack.
     * @param int $offset
     * The optional `$offset` parameter allows you to specify where in `$haystack` to
     * start searching as an offset in grapheme units. Defaults to **0**.
     * @return int|null
     * Position where the needle was found. **null** if no match was found.
     */
    public static function firstIndexOf(string $haystack, string $needle, int $offset = 0): ?int
    {
        $length = static::length($haystack);
        if (abs($offset) >= $length) {
            return null;
        }

        $result = grapheme_strpos($haystack, $needle, $offset);

        return $result !== false
            ? $result
            : null;
    }

    /**
     * Insert a substring before the given position.
     *
     * Example:
     * ```php
     * Str::insert('abc', 'xyz', 0); // 'xyzabc'
     * Str::insert('abc', 'xyz', 3); // 'abcxyz'
     * Str::insert('abc', 'xyz', -1); // 'abcxyz'
     * ```
     *
     * @param string $string
     * String to be inserted.
     * @param string $insert
     * String that will be inserted.
     * @param int $position
     * Position where the `$insert` will be inserted.
     * @return string
     * String which contains `$insert` string at `$position`.
     */
    public static function insert(string $string, string $insert, int $position): string
    {
        if ($position < 0) {
            ++$position;
        }

        return
            static::substring($string, 0, $position) .
            $insert .
            static::substring($string, $position);
    }

    /**
     * Determine whether a given string is blank.
     *
     * Example:
     * ```php
     * Str::isBlank(null); // true
     * Str::isBlank(''); // true
     * Str::isBlank('0'); // false
     * ```
     *
     * @param string|null $string
     * **string** or **null** variable to be checked.
     * @return bool
     * Returns **true** if variable is an empty string or null. **false** otherwise.
     */
    public static function isBlank(?string $string): bool
    {
        return $string === null || $string === '';
    }

    /**
     * Determine whether a given string is not blank.
     *
     * Example:
     * ```php
     * Str::isNotBlank('a'); // true
     * Str::isNotBlank('0'); // true
     * Str::isNotBlank(null); // false
     * Str::isNotBlank(''); // false
     * ```
     *
     * @param string|null $string
     * **string** or **null** variable to be checked.
     * @return bool
     * Returns **false** if variable is empty string or null. **true** otherwise.
     */
    public static function isNotBlank(?string $string): bool
    {
        return !static::isBlank($string);
    }

    /**
     * Convert the given string to kebab-case.
     *
     * Example:
     * ```php
     * Str::kebabCase('foo bar'); // 'foo-bar'
     * Str::kebabCase('foo_bar'); // 'foo-bar'
     * Str::kebabCase('FooBar'); // 'foo-bar'
     * Str::kebabCase('HTTPClient'); // 'http-client'
     * ```
     *
     * @param string $string
     * String to be converted to kebab-case.
     * @return string
     * Kebab-cased string.
     */
    public static function kebabCase(string $string): string
    {
        $converting = (string) preg_replace(['/([a-z\d])([A-Z])/', '/([^-])([A-Z][a-z])/'], '$1-$2', $string);
        $converting = (string) str_replace([' ', '_'], '-', $converting);
        return static::toLower($converting);
    }

    /**
     * Find position (in grapheme units) of last occurrence of substring in string.
     *
     * Example:
     * ```php
     * Str::lastIndexOf('abb', 'b'); // 2
     * Str::lastIndexOf('abb', 'b', 2); // 2
     * Str::lastIndexOf('abb', 'b', 3); // null
     * ```
     *
     * @param string $haystack
     * The string to look in.
     * @param string $needle
     * The substring to search for in the haystack.
     * @param int $offset
     * The optional `$offset` parameter allows you to specify where in `$haystack` to
     * start searching as an offset in grapheme units. Defaults to **0**.
     * @return int|null
     * Position where the needle was found. **null** if no match was found.
     */
    public static function lastIndexOf(string $haystack, string $needle, int $offset = 0): ?int
    {
        $length = static::length($haystack);
        if (abs($offset) >= $length) {
            return null;
        }

        $result = grapheme_strrpos($haystack, $needle, $offset);

        return $result !== false
            ? $result
            : null;
    }

    /**
     * Returns the length of the given string.
     * Works with multibyte and grapheme(emoji) strings.
     *
     * Example:
     * ```php
     * Str::length(''); // 0
     * Str::length('開発'); // 2
     * Str::length('👨‍👨‍👧‍👦'); // 1
     * ```
     *
     * If you want to know the bytes used for a given string instead, @see Str::bytes().
     *
     * @param string $string
     * The string being measured. Must be a valid UTF-8 encoded string.
     * @return int
     * The length of the string.
     */
    public static function length(string $string): int
    {
        $result = grapheme_strlen($string);

        if ($result === null) {
            throw new RuntimeException(intl_get_error_message());
        }

        // @codeCoverageIgnoreStart
        if ($result === false) {
            $message = 'Unknown internal error has occurred.' . PHP_EOL;
            $message.= 'Please see the link below for more info.' . PHP_EOL;
            $message.= 'https://github.com/php/php-src/blob/9bae9ab/ext/intl/grapheme/grapheme_string.c';
            throw new RuntimeException($message);
        }
        // @codeCoverageIgnoreEnd

        return $result;
    }

    /**
     * Perform a regular expression match
     *
     * Example:
     * ```php
     * Str::match('abcabc', '/a/'); // ['a']
     * Str::match('abcabc', '/(?<p1>a)bc/'); // ['abc', 'p1' => 'a', 'a']
     * ```
     *
     * @param string $string
     * The string to be matched.
     * @param string $pattern
     * The pattern to search for. Must be a valid regex.
     * @return array<int, array<string>>
     * Array of matches in multidimensional array.
     */
    public static function match(string $string, string $pattern): array
    {
        $match = [];

        preg_match($pattern, $string, $match);

        return $match;
    }

    /**
     * Perform a global regular expression match.
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
     * Determine if a substring is contained in a given string.
     *
     * Example:
     * ```php
     * Str::notContains('Foo bar', 'baz'); // true
     * ```
     *
     * @param string $haystack
     * The string to search in.
     * @param string $needle
     * The substring to search for in the `$haystack`.
     * @return bool
     * Returns **false** if `$needle` is in `$haystack`, **true** otherwise.
     */
    public static function notContains(string $haystack, string $needle): bool
    {
        return !static::contains($haystack, $needle);
    }

    /**
     * Pad a string on both ends to a certain length with another string.
     *
     * @param string $string
     * The string to be padded.
     * @param int $length
     * The length of the string once it has been padded.
     * If the value is lower than the length, the current string will be returned as-is.
     * @param string $pad
     * The string used for padding. Defaults to **' '**.
     * @return string
     * The padded string.
     */
    public static function padBoth(string $string, int $length, string $pad = ' '): string
    {
        return static::pad($string, $length, $pad, STR_PAD_BOTH);
    }

    /**
     * Pad a string on the left to a certain length with another string.
     *
     * @param string $string
     * The string to be padded.
     * @param int $length
     * The length of the string once it has been padded.
     * If the value is lower than the length, the current string will be returned as-is.
     * @param string $pad
     * The string used for padding. Defaults to **' '**.
     * @return string
     * The padded string.
     */
    public static function padLeft(string $string, int $length, string $pad = ' '): string
    {
        return static::pad($string, $length, $pad, STR_PAD_LEFT);
    }

    /**
     * Pad a string on the right to a certain length with another string.
     *
     * @param string $string
     * The string to be padded.
     * @param int $length
     * The length of the string once it has been padded.
     * If the value is lower than the length, the current string will be returned as-is.
     * @param string $pad
     * The string used for padding. Defaults to **' '**.
     * @return string
     * The padded string.
     */
    public static function padRight(string $string, int $length, string $pad = ' '): string
    {
        return static::pad($string, $length, $pad);
    }

    /**
     * Pad a string to a certain length with another string.
     *
     * @param string $string
     * The string to be padded.
     * @param int $length
     * The length of the string once it has been padded.
     * If the value is lower than the length of `$string`, the current string will be returned as-is.
     * @param string $pad
     * The string used for padding.
     * @param int $type
     * The padding type. Type can be STR_PAD_RIGHT, STR_PAD_LEFT, STR_PAD_BOTH. Defaults to **STR_PAD_RIGHT**
     * @return string
     * The padded string.
     */
    public static function pad(string $string, int $length, string $pad = ' ', int $type = STR_PAD_RIGHT): string
    {
        if ($length <= 0) {
            return $string;
        }

        $padLength = static::length($pad);

        if ($padLength === 0) {
            return $string;
        }

        $strLength = static::length($string);

        if ($type === STR_PAD_RIGHT) {
            $repeat = (int) ceil($length / $padLength);
            return $string . static::substring(str_repeat($pad, $repeat), 0, $length - $strLength);
        }

        if ($type === STR_PAD_LEFT) {
            $repeat = (int) ceil($length / $padLength);
            return static::substring(str_repeat($pad, $repeat), 0, $length - $strLength) . $string;
        }

        if ($type === STR_PAD_BOTH) {
            $halfLengthFraction = ($length - $strLength) / 2;
            $halfRepeat = (int) ceil($halfLengthFraction / $padLength);
            $prefixLength = (int) floor($halfLengthFraction);
            $suffixLength = (int) ceil($halfLengthFraction);
            $prefix = static::substring(str_repeat($pad, $halfRepeat), 0, $prefixLength);
            $suffix = static::substring(str_repeat($pad, $halfRepeat), 0, $suffixLength);
            return $prefix . $string . $suffix;
        }

        throw new LogicException('Invalid padding type: ' . $type);
    }

    /**
     * Convert the given string to pascal-case.
     *
     * Example:
     * ```php
     * Str::pascalCase('foo bar'); // 'FooBar'
     * Str::pascalCase('foo-bar'); // 'FooBar'
     * Str::pascalCase('foo_bar'); // 'FooBar'
     * Str::pascalCase('FooBar'); // 'FooBar'
     * ```
     *
     * @param string $string
     * String to be converted to pascal-case.
     * @return string
     * Pascal-cased string.
     */
    public static function pascalCase(string $string): string
    {
        return str_replace(['-', '_', ' '], '', ucwords($string, '-_ '));
    }

    /**
     * Repeat a string.
     *
     * Example:
     * ```php
     * Str::repeat('a', 3); // 'aaa'
     * ```
     *
     * @param string $string
     * The string to be repeated.
     * @param int<0, max> $times
     * Number of times to repeat `$string`.
     * @return string
     * The repeated string.
     */
    public static function repeat(string $string, int $times): string
    {
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
     * The string to search for.
     * @param string $replace
     * The string to replace.
     * @param int|null $limit
     * The maximum times a replacement occurs. Unlimited, if **null** is given. Defaults to **null**.
     * @return string
     * String with the replaced values.
     */
    public static function replace(string $string, string $search, string $replace, ?int $limit = null): string
    {
        Assert::greaterThanEq($limit, 0);

        if ($search === '') {
            return $string;
        }

        $search = '/' . preg_quote($search, '/') . '/su';

        return static::replaceMatch($string, $search, $replace, $limit);
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
     * The string to search for.
     * @param string $replace
     * The string to replace.
     * @return string
     * String with the replaced values.
     */
    public static function replaceFirst(string $string, string $search, string $replace): string
    {
        if ($search === '') {
            return $string;
        }

        $pos = strpos($string, $search);
        return $pos !== false
            ? substr_replace($string, $replace, $pos, strlen($search))
            : $string;
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
     * The string to search for.
     * @param string $replace
     * The string to replace.
     * @return string
     * String with the replaced values.
     */
    public static function replaceLast(string $string, string $search, string $replace): string
    {
        if ($search === '') {
            return $string;
        }

        $pos = strrpos($string, $search);
        return $pos !== false
            ? substr_replace($string, $replace, $pos, strlen($search))
            : $string;
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
     * @param string $replace
     * The string to replace.
     * @param int|null $limit
     * The maximum possible replacements for each pattern. Unlimited, if **null** is given. Defaults to **null**.
     * @return string
     * String with the replaced values.
     */
    public static function replaceMatch(string $string, string $pattern, string $replace, ?int $limit = null): string
    {
        Assert::greaterThanEq($limit, 0);

        if ($string === '') {
            return $string;
        }

        return (string) preg_replace($pattern, $replace, $string, $limit ?? -1);
    }

    /**
     * Reverse a string.
     *
     * Example:
     * ```php
     * Str::reverse('Foo'); // 'ooF'
     * Str::reverse('あい'); // 'いあ'
     * ```
     *
     * @param string $string
     * The string to be reversed.
     * @return string
     * Reversed string.
     */
    public static function reverse(string $string): string
    {
        $length = static::length($string);

        // strrev($string) can only reverse bytes, so it only works for single byte chars.
        // So call strrev only if we can confirm that it only contains single byte chars.
        if ($length === strlen($string)) {
            return strrev($string);
        }

        $parts = [];
        for ($i = $length - 1; $i >= 0; $i--) {
            $parts[] = static::substring($string, $i, 1);
        }
        return implode('', $parts);
    }

    /**
     * Checks if a string starts with a given substring(s).
     * `$needle` can be a string or an iterable list of strings.
     *
     * Example:
     * ```php
     * Str::startsWith('abc', 'a'); // true
     * Str::startsWith('abc', ['b', 'c']); // false
     * ```
     *
     * @param string $haystack
     * The string to look in.
     * @param string|iterable<array-key, string> $needle
     * The substring(s) to search for in the haystack.
     * @return bool
     * Returns **true** if `$haystack` starts with `$needle`, **false** otherwise.
     */
    public static function startsWith(string $haystack, string|iterable $needle): bool
    {
        $needles = is_iterable($needle) ? $needle : [$needle];
        foreach ($needles as $each) {
            if (str_starts_with($haystack, $each)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Convert the given string to snake-case.
     *
     * Example:
     * ```php
     * Str::snakeCase('foo bar'); // 'foo_bar'
     * Str::snakeCase('foo_bar'); // 'foo_bar'
     * Str::snakeCase('FooBar'); // 'foo_bar'
     * Str::snakeCase('HTTPClient'); // 'http_client'
     * ```
     *
     * @param string $string
     * String to be converted to snake-case.
     * @return string
     * Snake-cased string.
     */
    public static function snakeCase(string $string): string
    {
        $converting = (string) preg_replace(['/([a-z\d])([A-Z])/', '/([^_])([A-Z][a-z])/'], '$1_$2', $string);
        $converting = (string) str_replace([' ', '-'], '_', $converting);
        return static::toLower($converting);
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
     * @param string|iterable<int, string> $separator
     * The boundary string(s) used to split.
     * @param int<0, max>|null $limit
     * Maximum number of chunks.
     * @return array<int, string>
     * Returns an array of strings created by splitting.
     */
    public static function split(string $string, string|iterable $separator, ?int $limit = null): array
    {
        Assert::greaterThanEq($limit, 0);

        $separators = is_iterable($separator) ? $separator : [$separator];
        $separators = Arr::map($separators, static fn(string $str): string => preg_quote($str, '/'));
        $pattern = '/(' . implode('|', $separators) . ')/su';

        $splits = preg_split($pattern, $string, $limit ?? -1);

        assert($splits !== false);

        return $splits;
    }

    /**
     * Return a subset of given string.
     *
     * @param string $string
     * The input string to be sliced. Must be valid UTF-8 encoded string.
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
        $string = grapheme_substr($string, $offset, $length);

        if ($string === false) {
            throw new RuntimeException(intl_get_error_message());
        }

        return $string;
    }

    /**
     * Make a string lower case.
     * Supports multibyte strings.
     *
     * Example:
     * ```php
     * Str::toLower('AbCd'); // 'abcd'
     * Str::toLower('ÇĞİÖŞÜ'); // 'çği̇öşü'
     * ```
     *
     * @param string $string
     * The string being lower-cased.
     * @return string
     * String with all alphabetic characters converted to lower case.
     */
    public static function toLower(string $string): string
    {
        return mb_strtolower($string, self::ENCODING);
    }

    /**
     * Make a string upper case.
     * Supports multibyte strings.
     *
     * Example:
     * ```php
     * Str::toUpper('AbCd'); // 'ABCD'
     * Str::toUpper('çği̇öşü'); // ÇĞİÖŞÜ
     * ```
     *
     * @param string $string
     * The string being upper-cased.
     * @return string
     * String with all alphabetic characters converted to upper case.
     */
    public static function toUpper(string $string): string
    {
        return mb_strtoupper($string, self::ENCODING);
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
     * @param string $character
     * Characters that would be stripped.
     * Defaults to PCRE spaces. (https://www.pcre.org/original/doc/html/pcrepattern.html)
     * @return string
     * The trimmed string.
     */
    public static function trim(string $string, string $character = '\s'): string
    {
        return static::trimEnd(static::trimStart($string, $character), $character);
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
     * @param string $character
     * Characters that would be stripped.
     * Defaults to PCRE spaces. (https://www.pcre.org/original/doc/html/pcrepattern.html)
     * @return string
     * The trimmed string.
     */
    public static function trimEnd(string $string, string $character = '\s'): string
    {
        $result = preg_replace('/[' . $character . ']*$/su', '', $string);

        if ($result === null) {
            return $string;
        }

        return $result;
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
     * @param string $character
     * Characters that would be stripped.
     * Defaults to PCRE spaces. (https://www.pcre.org/original/doc/html/pcrepattern.html)
     * @return string
     * The trimmed string.
     */
    public static function trimStart(string $string, string $character = '\s'): string
    {
        $result = preg_replace('/^[' . $character . ']*/su', '', $string);

        if ($result === null) {
            return $string;
        }

        return $result;
    }

    /**
     * Wraps a string to a given number of characters.
     *
     * Example:
     * ```php
     * Str::wordWrap('wrap around', 1, overflow: true); // "wrap\naround"
     * Str::wordWrap('wrap around', 3); // "wra\np\naro\nund"
     * ```
     *
     * @param string $string
     * The string to be wrapped.
     * @param int $width
     * The number of characters at which the string will be wrapped. Defaults to **80**.
     * @param string $break
     * String used for wrapping. Defaults to **"\n"**.
     * @param bool $overflow
     * Allow overflowing of words. Defaults to **false**.
     * @return string
     * String wrapped at the specified length.
     */
    public static function wordWrap(string $string, int $width = 80, string $break = "\n", bool $overflow = false): string
    {
        return wordwrap($string, $width, $break, !$overflow);
    }
}
