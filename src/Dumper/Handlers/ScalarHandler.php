<?php declare(strict_types=1);

namespace Kirameki\Dumper\Handlers;

use SouthPointe\Ansi\Codes\Color;
use function count;
use function explode;
use function implode;
use function is_bool;
use function is_float;
use function is_infinite;
use function is_int;
use function is_nan;
use function mb_ord;
use function mb_strcut;
use function mb_strlen;
use function preg_replace_callback_array;
use function sprintf;
use function str_contains;
use const PHP_EOL;

class ScalarHandler extends Handler
{
    /**
     * @param bool|int|float|string $var
     * @param int $depth
     * @return string
     */
    public function handle(bool|int|float|string $var, int $depth): string
    {
        if (is_bool($var)) {
            return $this->handleBool($var);
        }
        if (is_int($var)) {
            return $this->handleInt($var);
        }
        if (is_float($var)) {
            return $this->handleFloat($var);
        }
        return $this->handleString($var, $depth);
    }

    /**
     * @param bool $var
     * @return string
     */
    protected function handleBool(bool $var): string
    {
        return $this->colorizeScalar($var ? 'true' : 'false');
    }

    /**
     * @param int $var
     * @return string
     */
    protected function handleInt(int $var): string
    {
        return $this->colorizeScalar((string) $var);
    }

    /**
     * @param float $var
     * @return string
     */
    protected function handleFloat(float $var): string
    {
        $string = (string) $var;

        if (str_contains($string, '.') || is_nan($var) || is_infinite($var)) {
            return $this->colorizeScalar($string);
        }

        return $this->colorizeScalar($string . '.0');
    }

    /**
     * @param string $var
     * @param int $depth
     * @return string
     */
    protected function handleString(string $var, int $depth): string
    {
        $tooLong = mb_strlen($var) > $this->config->maxStringLength;

        // Trim the string if too long
        // Ellipsis will be added after control and space replacement.
        if ($tooLong) {
            $var = mb_strcut($var, 0, $this->config->maxStringLength);
        }

        $lines = explode(PHP_EOL, $var);
        $lineCount = count($lines);

        $multiLine = match ($lineCount) {
            1 => false,
            2 => $lines[1] !== '', // newline at the end don't count
            default => true,
        };

        $decoratedLines = [];
        for ($i = 0, $size = $lineCount; $i < $size; $i++) {
            $line = $lines[$i];
            $shouldIndent = !($i === $size - 1 && $line === '');

            // Replace control and space chars with raw string representation.
            $line = (string)preg_replace_callback_array([
                '/[\pC]/u' => fn(array $match) => $this->handleControlChar($match[0]),
                '/[\pZ]/u' => fn(array $match) => $this->handleSpaceChar($match[0]),
            ], $line);

            $line = $this->colorizeScalar($line);

            $decoratedLines[] = $multiLine && $shouldIndent
                ? $this->indentMultiLine($depth) . $line
                : $line;
        }

        $decorated = implode($this->colorizeEscaped("\\n"), $decoratedLines);

        if ($tooLong) {
            $decorated .= $this->colorizeComment(' … <truncated>');
        }

        if ($multiLine) {
            return $this->colorizeComment('"""') .
                $decorated .
                $this->indentMultiLine($depth) .
                $this->colorizeComment('"""');
        }

        return
            $this->colorizeComment('"') .
            $decorated .
            $this->colorizeComment('"');
    }

    /**
     * @param string $char
     * @return string
     */
    protected function handleControlChar(string $char): string
    {
        // Use shorthand representation, where possible.
        $escaped = match ($char) {
            "\0" => '\0',
            "\e" => '\e',
            "\f" => '\f',
            "\n" => '\n',
            "\r" => '\r',
            "\t" => '\t',
            "\v" => '\v',
            default => null,
        };

        if ($escaped === null) {
            $codepoint = mb_ord($char);
            $escaped = $codepoint > 255
                ? sprintf('\u%04X', $codepoint)
                : sprintf('\x%02X', $codepoint);
        }

        return $this->colorizeEscaped($escaped);
    }

    /**
     * @param string $space
     * @return string
     */
    protected function handleSpaceChar(string $space): string
    {
        if ($space === ' ') {
            return $space;
        }
        $escaped = sprintf('\u%02X', mb_ord($space));
        return $this->colorizeEscaped($escaped);
    }

    /**
     * @param string $string
     * @return string
     */
    protected function colorizeEscaped(string $string): string
    {
        $deco = $this->decorator;

        return
            $deco->colorEnd() .
            $deco->colorStart(Color::DarkOrange3_B) .
            $string .
            $deco->colorEnd() .
            $deco->colorStart($this->scalarColor);
    }

    /**
     * @param int $depth
     * @return string
     */
    protected function indentMultiLine(int $depth): string
    {
        // Indent only if string has depth
        if ($depth !== 0) {
            return $this->eol() . $this->indent('', $depth + 1);
        }

        return $this->eol();
    }
}
