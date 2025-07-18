<?php declare(strict_types=1);

namespace Kirameki\Text;

use IntlException;
use Kirameki\Core\Exceptions\ExtensionRequiredException;
use Kirameki\Core\Exceptions\InvalidArgumentException;
use Kirameki\Core\Exceptions\LogicException;
use RuntimeException;
use ValueError;
use function array_reverse;
use function assert;
use function ceil;
use function extension_loaded;
use function floor;
use function grapheme_extract;
use function grapheme_strlen;
use function grapheme_strpos;
use function grapheme_strrpos;
use function grapheme_substr;
use function implode;
use function ini_get;
use function mb_strtolower;
use function mb_strtoupper;
use function str_repeat;
use function strlen;
use function strrev;
use const GRAPHEME_EXTR_COUNT;
use const PHP_EOL;
use const STR_PAD_BOTH;
use const STR_PAD_LEFT;
use const STR_PAD_RIGHT;

class Utf8 extends Str
{
    public const WHITESPACE = '\s';

    protected static bool $setupChecked = false;

    /**
     * Counts the size of bytes for the given string.
     *
     * Example:
     * ```php
     * Utf8::byteLength('a'); // 1
     * Utf8::byteLength('あ'); // 3
     * Utf8::byteLength('👨‍👨‍👧‍👦'); // 25
     * ```
     *
     * @param string $string
     * The target string being counted.
     * @return int
     * The byte size of the given string.
     */
    public static function byteLength(string $string): int
    {
        return strlen($string);
    }

    /**
     * Determine if a string contains a given substring.
     *
     * Example:
     * ```php
     * Utf8::contains('Foo bar', 'bar'); // true
     * Utf8::contains('👨‍👨‍👧‍👧‍', '👨'); // false
     * Utf8::contains('a', ''); // true
     * Utf8::contains('', ''); // true
     * ```
     *
     * @inheritDoc
     */
    public static function contains(string $string, string $substring): bool
    {
        return grapheme_strpos($string, $substring) !== false;
    }

    /**
     * Extracts the substring from string based on the given position.
     * The position is determined by bytes.
     * If the position happens to be between a multibyte character, the cut is performed on the entire character.
     * Grapheme string is not supported for this method.
     *
     * Example:
     * ```php
     * Utf8::cut('abc', 1); // 'a'
     * Utf8::cut('あいう', 1); // '' since あ is 3 bytes long.
     * ```
     *
     * @param string $string
     * The string to look in.
     * @param int $position
     * The position where the string will be cut.
     * @param string $ellipsis
     * [Optional] An ellipsis which will be appended to the cut string if string is greater than cut string. Defaults to **''**.
     * @return string
     */
    public static function cut(string $string, int $position, string $ellipsis = self::EMPTY): string
    {
        static::assertIntlSetup();

        if ($string === '') {
            return $string;
        }

        $parts = [];
        $addEllipsis = true;

        try {
            $offset = 0;
            while ($offset <= $position) {
                $char = grapheme_extract($string, 1, GRAPHEME_EXTR_COUNT, $offset, $offset);
                if ($offset > $position) {
                    break;
                }
                $parts[] = $char;
            }
        } catch (IntlException $e) {
            if ($e->getMessage() === 'grapheme_extract: start not contained in string') {
                $addEllipsis = false;
            } else {
                // @codeCoverageIgnoreStart
                throw $e;
                // @codeCoverageIgnoreEnd
            }
        }

        if ($ellipsis !== self::EMPTY && $addEllipsis) {
            $parts[] = $ellipsis;
        }

        return implode(self::EMPTY, $parts);
    }

    /**
     * Checks if a string ends with a given suffix(s).
     * `$suffix` can be a string or an iterable list of strings.
     *
     * Example:
     * ```php
     * Utf8::endsWith('abc', 'c'); // true
     * Utf8::endsWith('abc', ['a', 'b']); // false
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
        if ($suffix === self::EMPTY) {
            return true;
        }
        if (static::substring($string, -static::length($suffix)) === $suffix) {
            return true;
        }
        return false;
    }

    /**
     * Find position (in grapheme units) of first occurrence of substring in string.
     *
     * Example:
     * ```php
     * Utf8::firstIndexOf('abb', 'b'); // 1
     * Utf8::firstIndexOf('abb', 'b', 2); // 2
     * Utf8::firstIndexOf('abb', 'b', 3); // null
     * ```
     *
     * @inheritDoc
     */
    public static function indexOfFirst(string $string, string $substring, int $offset = 0): ?int
    {
        try {
            $result = grapheme_strpos($string, $substring, $offset);
            return $result !== false ? $result : null;
        } catch (ValueError $e) {
            if ($e->getMessage() === 'grapheme_strpos(): Argument #3 ($offset) must be contained in argument #1 ($haystack)') {
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
     * Utf8::indexOfLast('abb', 'b'); // 2
     * Utf8::indexOfLast('abb', 'b', 2); // 2
     * Utf8::indexOfLast('abb', 'b', 3); // null
     * ```
     *
     * @inheritDoc
     */
    public static function indexOfLast(string $string, string $substring, int $offset = 0): ?int
    {
        try {
            $result = grapheme_strrpos($string, $substring, $offset);
            return $result !== false ? $result : null;
        } catch (ValueError $e) {
            if ($e->getMessage() === 'grapheme_strrpos(): Argument #3 ($offset) must be contained in argument #1 ($haystack)') {
                return null;
            }
            // @codeCoverageIgnoreStart
            throw $e;
            // @codeCoverageIgnoreEnd
        }
    }

    /**
     * Returns the length of the given string.
     * Works with multibyte and grapheme(emoji) strings.
     *
     * Example:
     * ```php
     * Utf8::length(''); // 0
     * Utf8::length('開発'); // 2
     * Utf8::length('👨‍👨‍👧‍👦'); // 1
     * ```
     *
     * @inheritDoc
     */
    public static function length(string $string): int
    {
        static::assertIntlSetup();

        $result = grapheme_strlen($string);

        // @codeCoverageIgnoreStart
        if ($result === null || $result === false) {
            throw new RuntimeException(
                'Unknown internal error has occurred.' . PHP_EOL .
                'Please see the link below for more info.' . PHP_EOL .
                'https://github.com/php/php-src/blob/9bae9ab/ext/intl/grapheme/grapheme_string.c'
            );
        }
        // @codeCoverageIgnoreEnd

        return $result;
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
        if ($length <= 0) {
            return $string;
        }

        $padLength = static::length($padding);

        if ($padLength === 0) {
            return $string;
        }

        $strLength = static::length($string);

        if ($type === STR_PAD_RIGHT) {
            $repeat = (int) ceil($length / $padLength);
            return $string . static::substring(str_repeat($padding, $repeat), 0, $length - $strLength);
        }

        if ($type === STR_PAD_LEFT) {
            $repeat = (int) ceil($length / $padLength);
            return static::substring(str_repeat($padding, $repeat), 0, $length - $strLength) . $string;
        }

        if ($type === STR_PAD_BOTH) {
            $halfLengthFraction = ($length - $strLength) / 2;
            $halfRepeat = (int) ceil($halfLengthFraction / $padLength);
            $prefixLength = (int) floor($halfLengthFraction);
            $suffixLength = (int) ceil($halfLengthFraction);
            $prefix = static::substring(str_repeat($padding, $halfRepeat), 0, $prefixLength);
            $suffix = static::substring(str_repeat($padding, $halfRepeat), 0, $suffixLength);
            return $prefix . $string . $suffix;
        }

        throw new InvalidArgumentException("Unknown padding type: {$type}.", [
            'string' => $string,
            'length' => $length,
            'padding' => $padding,
            'type' => $type,
        ]);
    }

    /**
     * @inheritDoc
     */
    public static function reverse(string $string): string
    {
        $bytes = strlen($string);

        // strrev($string) can only reverse bytes, so it only works for single byte chars.
        // So call strrev only if we can confirm that it only contains single byte chars.
        if (static::length($string) === $bytes) {
            return strrev($string);
        }

        $offset = 0;
        $parts = [];
        while ($offset < $bytes) {
            $char = grapheme_extract($string, 1, GRAPHEME_EXTR_COUNT, $offset, $offset);
            if ($char !== false) {
                $parts[] = $char;
            }
        }
        return implode(self::EMPTY, array_reverse($parts));
    }

    /**
     * Checks if a string starts with a given substring(s).
     * `$prefix` can be a string or an iterable list of strings.
     *
     * Example:
     * ```php
     * Utf8::startsWith('abc', 'a'); // true
     * Utf8::startsWith('abc', 'b'); // false
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
        if ($prefix === self::EMPTY) {
            return true;
        }
        if (static::substring($string, 0, static::length($prefix)) === $prefix) {
            return true;
        }
        return false;
    }

    /**
     * Return a subset of given string.
     * If offset is out of range, a RuntimeException is thrown unless a fallback string is defined.
     *
     * Example:
     * ```php
     * Utf8::substring('abc', 1); // 'a'
     * Utf8::substring('abc', 0, 1); // 'a'
     * Utf8::substring('abc', 1, 2); // 'bc'
     * Utf8::substring('a', 1); // RuntimeException: Offset: 1 is out of range for string "a"
     * Utf8::substring('👨‍👨‍👧‍👦', 1, 'not found'); // 'not found'
     * ```
     *
     * @inheritDoc
     */
    public static function substring(string $string, int $offset, ?int $length = null): string
    {
        self::assertIntlSetup();

        $substring = grapheme_substr($string, $offset, $length);
        assert($substring !== false);
        return $substring;
    }

    /**
     * Convert the given string to lower case.
     * Supports multibyte strings.
     *
     * Example:
     * ```php
     * Utf8::toLowerCase('AbCd'); // 'abcd'
     * Utf8::toLowerCase('ÇĞİÖŞÜ'); // 'çği̇öşü'
     * ```
     *
     * @param string $string
     * The string being lower-cased.
     * @return string
     * String with all alphabetic characters converted to lower case.
     */
    public static function toLowerCase(string $string): string
    {
        return mb_strtolower($string);
    }

    /**
     * Convert the given string to upper case.
     * Supports multibyte strings.
     *
     * Example:
     * ```php
     * Utf8::toUpperCase('AbCd'); // 'ABCD'
     * Utf8::toUpperCase('çği̇öşü'); // ÇĞİÖŞÜ
     * ```
     *
     * @inheritDoc
     */
    public static function toUpperCase(string $string): string
    {
        return mb_strtoupper($string);
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

        $result = preg_replace('/[' . $characters . ']*$/su', self::EMPTY, $string);

        return $result !== null
            ? $result
            : $string;
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

        $result = preg_replace('/^[' . $characters . ']*/su', self::EMPTY, $string);

        return $result !== null
            ? $result
            : $string;
    }

    /**
     * @return void
     */
    protected static function assertIntlSetup(): void
    {
        if (static::$setupChecked) {
            return;
        }

        if (!extension_loaded('intl')) {
            // @codeCoverageIgnoreStart
            throw new ExtensionRequiredException('extension: "intl" is required to use this method.');
            // @codeCoverageIgnoreEnd
        }

        if (!ini_get('intl.use_exceptions')) {
            throw new LogicException('"intl.use_exceptions" must be enabled to use this method.');
        }

        static::$setupChecked = true;
    }

    /**
     * @internal
     */
    public static function resetSetupCheckedFlag(): void
    {
        static::$setupChecked = false;
    }
}
