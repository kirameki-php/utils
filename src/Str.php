<?php declare(strict_types=1);

namespace Kirameki\Utils;

use LogicException;
use RuntimeException;
use Webmozart\Assert\Assert;
use function abs;
use function array_map;
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
    public const Encoding = 'UTF-8';

    /**
     * @param string $string
     * @param string $search
     * @return string
     */
    public static function after(string $string, string $search): string
    {
        $pos = grapheme_strpos($string, $search);

        // If string is not matched, return blank immediately.
        if ($pos === false) {
            return '';
        }

        return (string) grapheme_substr($string, $pos + grapheme_strlen($search));
    }

    /**
     * @param string $string
     * @param int $position
     * @return string
     */
    public static function afterIndex(string $string, int $position): string
    {
        return (string) grapheme_substr($string, $position);
    }

    /**
     * @param string $string
     * @param string $search
     * @return string
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

        return (string) grapheme_substr($string, $pos + grapheme_strlen($search));
    }

    /**
     * @param string $string
     * @param string $search
     * @return string
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

        return (string) grapheme_substr($string, 0, $pos);
    }

    /**
     * @param string $string
     * @param int $position
     * @return string
     */
    public static function beforeIndex(string $string, int $position): string
    {
        return (string) grapheme_substr($string, 0, $position);
    }

    /**
     * @param string $string
     * @param string $search
     * @return string
     */
    public static function beforeLast(string $string, string $search): string
    {
        $pos = grapheme_strrpos($string, $search);

        // If string is not matched, return itself immediately.
        if ($pos === false) {
            return $string;
        }

        return (string) grapheme_substr($string, 0, $pos);
    }

    /**
     * @param string $string
     * @param string $from
     * @param string $to
     * @return string
     */
    public static function between(string $string, string $from, string $to): string
    {
        return static::beforeLast(static::after($string, $from), $to);
    }

    /**
     * @param string $string
     * @return string
     */
    public static function camelCase(string $string): string
    {
        return static::lcFirst(static::pascalCase($string));
    }

    /**
     * @param string $string
     * @return string
     */
    public static function capitalize(string $string): string
    {
        $firstChar = mb_strtoupper((string) grapheme_substr($string, 0, 1));
        $otherChars = grapheme_substr($string, 1);
        return $firstChar . $otherChars;
    }

    /**
     * @param string ...$string
     * @return string
     */
    public static function concat(string ...$string): string
    {
        return implode('', $string);
    }

    /**
     * @param string $haystack
     * @param string $needle
     * @return bool
     */
    public static function contains(string $haystack, string $needle): bool
    {
        return str_contains($haystack, $needle);
    }

    /**
     * @param string $haystack
     * @param iterable<array-key, string> $needles
     * @return bool
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
     * @param string $haystack
     * @param iterable<array-key, string> $needles
     * @return bool
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
     * @param string $string
     * @param string $pattern
     * @return bool
     */
    public static function containsPattern(string $string, string $pattern): bool
    {
        return (bool) preg_match($pattern, $string);
    }

    /**
     * @param string $string
     * @param int $byteLimit
     * @param string $ellipsis
     * @return string
     */
    public static function cut(string $string, int $byteLimit, string $ellipsis = ''): string
    {
        $cut = mb_strcut($string, 0, $byteLimit, self::Encoding);
        if ($ellipsis !== '' && mb_strlen($cut) < mb_strlen($string)) {
            $cut .= $ellipsis;
        }
        return $cut;
    }

    /**
     * @param string $string
     * @param string $search
     * @param int|null $limit
     * @return string
     */
    public static function delete(string $string, string $search, ?int $limit = null): string
    {
        return static::replace($string, $search, '', $limit);
    }

    /**
     * @param string $haystack
     * @param string|iterable<array-key, string> $needle
     * @return bool
     */
    public static function doesNotEndWith(string $haystack, string|iterable $needle): bool
    {
        return !static::endsWith($haystack, $needle);
    }

    /**
     * @param string $haystack
     * @param string|iterable<array-key, string> $needle
     * @return bool
     */
    public static function doesNotStartWith(string $haystack, string|iterable $needle): bool
    {
        return !static::startsWith($haystack, $needle);
    }

    /**
     * @param string $haystack
     * @param string|iterable<array-key, string> $needle
     * @return bool
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
     * @param string $string
     * @param string $search
     * @param int $offset
     * @return int|false
     */
    public static function firstIndexOf(string $string, string $search, int $offset = 0): int|false
    {
        $length = grapheme_strlen($string);
        if (abs($offset) > $length) {
            return false;
        }
        return grapheme_strpos($string, $search, $offset);
    }

    /**
     * @param string $string
     * @param string $insert
     * @param int $position
     * @return string
     */
    public static function insert(string $string, string $insert, int $position): string
    {
        return
            grapheme_substr($string, 0, $position) .
            $insert .
            grapheme_substr($string, $position);
    }

    /**
     * @param string|null $string
     * @return bool
     */
    public static function isBlank(?string $string): bool
    {
        return $string === null || $string === '';
    }

    /**
     * @param string|null $string
     * @return bool
     */
    public static function isNotBlank(?string $string): bool
    {
        return !static::isBlank($string);
    }

    /**
     * @param string $string
     * @return string
     */
    public static function kebabCase(string $string): string
    {
        $converting = (string) preg_replace(['/([a-z\d])([A-Z])/', '/([^-])([A-Z][a-z])/'], '$1-$2', $string);
        $converting = (string) str_replace([' ', '_'], '-', $converting);
        return mb_strtolower($converting, self::Encoding);
    }

    /**
     * @param string $string
     * @param string $search
     * @param int $offset
     * @return int|false
     */
    public static function lastIndexOf(string $string, string $search, int $offset = 0): int|false
    {
        $length = grapheme_strlen($string);
        if (abs($offset) > $length) {
            return false;
        }
        return grapheme_strrpos($string, $search, $offset);
    }

    /**
     * @param string $string
     * @return string
     */
    public static function lcFirst(string $string): string
    {
        $firstChar = mb_strtolower(static::substring($string, 0, 1), static::Encoding);
        $otherChars = static::substring($string, 1);
        return $firstChar . $otherChars;
    }

    /**
     * @param string $string
     * @return int
     */
    public static function length(string $string): int
    {
        return (int) grapheme_strlen($string);
    }

    /**
     * @param string $string
     * @param string $pattern
     * @return array<int, array<string>>
     */
    public static function match(string $string, string $pattern): array
    {
        $match = [];
        preg_match($pattern, $string, $match);
        return $match;
    }

    /**
     * @param string $string
     * @param string $pattern
     * @return array<int, array<string>>
     */
    public static function matchAll(string $string, string $pattern): array
    {
        $match = [];
        preg_match_all($pattern, $string, $match);
        return $match;
    }

    /**
     * @param string $string
     * @return string
     */
    public static function ucFirst(string $string): string
    {
        $firstChar = mb_strtoupper(static::substring($string, 0, 1), static::Encoding);
        $otherChars = static::substring($string, 1);
        return $firstChar . $otherChars;
    }

    /**
     * @param string $haystack
     * @param string $needle
     * @return bool
     */
    public static function notContains(string $haystack, string $needle): bool
    {
        return !static::contains($haystack, $needle);
    }

    /**
     * @param string $string
     * @param int $length
     * @param string $pad
     * @return string
     */
    public static function padBoth(string $string, int $length, string $pad = ' '): string
    {
        return static::pad($string, $length, $pad, STR_PAD_BOTH);
    }

    /**
     * @param string $string
     * @param int $length
     * @param string $pad
     * @return string
     */
    public static function padLeft(string $string, int $length, string $pad = ' '): string
    {
        return static::pad($string, $length, $pad, STR_PAD_LEFT);
    }

    /**
     * @param string $string
     * @param int $length
     * @param string $pad
     * @return string
     */
    public static function padRight(string $string, int $length, string $pad = ' '): string
    {
        return static::pad($string, $length, $pad);
    }

    /**
     * @param string $string
     * @param int $length
     * @param string $pad
     * @param int<0, 2> $type
     * @return string
     */
    public static function pad(string $string, int $length, string $pad = ' ', int $type = STR_PAD_RIGHT): string
    {
        if ($length <= 0) {
            return $string;
        }

        $padLength = grapheme_strlen($pad);

        if ($padLength === 0) {
            return $string;
        }

        $strLength = grapheme_strlen($string);

        if ($type === STR_PAD_RIGHT) {
            $repeat = (int) ceil($length / $padLength);
            return $string . grapheme_substr(str_repeat($pad, $repeat), 0, $length - $strLength);
        }

        if ($type === STR_PAD_LEFT) {
            $repeat = (int) ceil($length / $padLength);
            return grapheme_substr(str_repeat($pad, $repeat), 0, $length - $strLength) . $string;
        }

        if ($type === STR_PAD_BOTH) {
            $halfLengthFraction = ($length - $strLength) / 2;
            $halfRepeat = (int) ceil($halfLengthFraction / $padLength);
            $prefixLength = (int) floor($halfLengthFraction);
            $suffixLength = (int) ceil($halfLengthFraction);
            $prefix = grapheme_substr(str_repeat($pad, $halfRepeat), 0, $prefixLength);
            $suffix = grapheme_substr(str_repeat($pad, $halfRepeat), 0, $suffixLength);
            return $prefix . $string . $suffix;
        }

        throw new LogicException('Invalid padding type: ' . $type);
    }

    /**
     * @param string $string
     * @return string
     */
    public static function pascalCase(string $string): string
    {
        return str_replace(['-', '_', ' '], '', ucwords($string, '-_ '));
    }

    /**
     * @param string $string
     * @param int<0, max> $times
     * @return string
     */
    public static function repeat(string $string, int $times): string
    {
        return str_repeat($string, $times);
    }

    /**
     * @param string $string
     * @param string $search
     * @param string $replace
     * @param int|null $limit
     * @return string
     */
    public static function replace(string $string, string $search, string $replace, ?int $limit = null): string
    {
        Assert::greaterThanEq($limit, 0);

        if ($search === '') {
            return $string;
        }

        return static::replaceMatch($string, "/\Q$search\E/", $replace, $limit);
    }

    /**
     * @param string $string
     * @param string $search
     * @param string $replace
     * @return string
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
     * @param string $string
     * @param string $search
     * @param string $replace
     * @return string
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
     * @param string $string
     * @param string $pattern
     * @param string $replace
     * @param int|null $limit
     * @return string
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
     * @param string $string
     * @return string
     */
    public static function reverse(string $string): string
    {
        $length = grapheme_strlen($string);

        // strrev($string) can only reverse bytes, so it only works for single byte chars.
        // So call strrev only if we can confirm that it only contains single byte chars.
        if ($length === strlen($string)) {
            return strrev($string);
        }

        $parts = [];
        for ($i = $length - 1; $i >= 0; $i--) {
            $parts[] = grapheme_substr($string, $i, 1);
        }
        return implode('', $parts);
    }

    /**
     * @param string $haystack
     * @param string|iterable<array-key, string> $needle
     * @return bool
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
     * @param string $string
     * @return string
     */
    public static function snakeCase(string $string): string
    {
        $converting = (string) preg_replace(['/([a-z\d])([A-Z])/', '/([^_])([A-Z][a-z])/'], '$1_$2', $string);
        $converting = (string) str_replace([' ', '-'], '_', $converting);
        return mb_strtolower($converting, self::Encoding);
    }

    /**
     * @param string $string
     * @param string|iterable<array-key, string> $separator
     * @param int<0, max>|null $limit
     * @return array<int, string>
     */
    public static function split(string $string, string|iterable $separator, ?int $limit = null): array
    {
        Assert::greaterThanEq($limit, 0);

        $separators = Arr::wrap($separator);
        $separators = array_map(static fn(string $str): string => preg_quote($str, '/'), $separators);
        $pattern = '/(' . implode('|', $separators) . ')/';

        $splits = preg_split($pattern, $string, $limit ?? -1);

        assert($splits !== false);

        return $splits;
    }

    /**
     * @param string $string
     * @param int $offset
     * @param int|null $length
     * @return string
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
     * @param string $string
     * @return string
     */
    public static function toLower(string $string): string
    {
        return mb_strtolower($string, self::Encoding);
    }

    /**
     * @param string $string
     * @return string
     */
    public static function toUpper(string $string): string
    {
        return mb_strtoupper($string, self::Encoding);
    }

    /**
     * @param string $string
     * @param string $character
     * @return string
     */
    public static function trim(string $string, string $character = " \t\n\r\0\x0B"): string
    {
        return trim($string, $character);
    }

    /**
     * @param string $string
     * @param string $character
     * @return string
     */
    public static function trimEnd(string $string, string $character = " \t\n\r\0\x0B"): string
    {
        return rtrim($string, $character);
    }

    /**
     * @param string $string
     * @param string $character
     * @return string
     */
    public static function trimStart(string $string, string $character = " \t\n\r\0\x0B"): string
    {
        return ltrim($string, $character);
    }

    /**
     * @param string $string
     * @param int $width
     * @param string $break
     * @param bool $overflow
     * @return string
     */
    public static function wordWrap(string $string, int $width = 80, string $break = "\n", bool $overflow = false): string
    {
        return wordwrap($string, $width, $break, !$overflow);
    }
}
