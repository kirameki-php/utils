<?php declare(strict_types=1);

namespace Kirameki\Dumper\Decorators;

use Kirameki\Dumper\Config;
use SouthPointe\Ansi\Ansi;
use SouthPointe\Ansi\Codes\Color;
use function str_repeat;
use const PHP_EOL;

class AnsiDecorator implements Decorator
{
    /**
     * @param Config $config
     */
    public function __construct(
        protected Config $config,
    )
    {
    }

    /**
     * @inheritDoc
     */
    public function root(string $string): string
    {
        return $string . $this->eol();
    }

    /**
     * @inheritDoc
     */
    public function line(string $string, int $depth): string
    {
        return $this->indent($string, $depth) . $this->eol();
    }

    /**
     * @inheritDoc
     */
    public function indent(string $string, int $depth): string
    {
        return str_repeat(' ', $depth * $this->config->indentSize) . $string;
    }

    /**
     * @inheritDoc
     */
    public function eol(): string
    {
        return PHP_EOL;
    }

    /**
     * @inheritDoc
     */
    public function colorStart(Color $color): string
    {
        return Ansi::fgColor($color);
    }

    /**
     * @inheritDoc
     */
    public function colorEnd(): string
    {
        return Ansi::resetStyle();
    }
}
