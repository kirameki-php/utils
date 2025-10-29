<?php declare(strict_types=1);

namespace Kirameki\Dumper;

use Kirameki\Dumper\Configs\DebugInfo;
use Kirameki\Dumper\Decorators\AnsiDecorator;
use Kirameki\Dumper\Decorators\Decorator;
use Kirameki\Dumper\Decorators\HtmlDecorator;
use Kirameki\Dumper\Decorators\PlainDecorator;
use ReflectionProperty;
use const PHP_SAPI;

/**
 * @phpstan-consistent-constructor
 */
class Config
{
    protected const int PROPERTY_FILTER_DEFAULT =
        ReflectionProperty::IS_STATIC |
        ReflectionProperty::IS_PUBLIC |
        ReflectionProperty::IS_PROTECTED |
        ReflectionProperty::IS_PRIVATE |
        ReflectionProperty::IS_PRIVATE_SET |
        ReflectionProperty::IS_PROTECTED_SET;

    /**
     * @var static|null
     */
    protected static ?self $default;

    /**
     * @var Formatter
     */
    public readonly Formatter $formatter;

    /**
     * @return static
     */
    public static function getDefault(): static
    {
        return static::$default ??= new static();
    }

    /**
     * @param static $instance
     * @return void
     */
    public static function setDefault(self $instance): void
    {
        static::$default = $instance;
    }

    /**
     * @param Formatter|null $formatter
     * @param Writer $writer
     * @param string|null $decorator
     * @param int $indentSize
     * @param int $maxStringLength
     * @param string $dateTimeFormat
     * @param int $propertyFilter
     * @param DebugInfo $debugInfo
     */
    public function __construct(
        ?Formatter $formatter = null,
        public readonly ?string $decorator = null,
        public readonly int $indentSize = 2,
        public readonly int $maxStringLength = 5000,
        public readonly string $dateTimeFormat = 'Y-m-d H:i:s.uP',
        public readonly int $propertyFilter = self::PROPERTY_FILTER_DEFAULT,
        public readonly DebugInfo $debugInfo = DebugInfo::Overwrite,
        public readonly Writer $writer = new Writer(),
    )
    {
        $this->formatter = $formatter ?? $this->makeFormatter();
    }

    /**
     * @return Formatter
     */
    protected function makeFormatter(): Formatter
    {
        return new Formatter($this->makeDefaultDecorator(), $this);
    }

    /**
     * @return Decorator
     */
    protected function makeDefaultDecorator(): Decorator
    {
        return match ($this->decorator ?? $this->guessDecoratorName()) {
            'cli' => new AnsiDecorator($this),
            'html' => new HtmlDecorator($this),
            default => new PlainDecorator($this),
        };
    }

    protected function guessDecoratorName(): string
    {
        return match (true) {
            PHP_SAPI === 'cli' => 'cli',
            isset($_SERVER['HTTPS']) => 'html',
            default => 'plain',
        };
    }
}
