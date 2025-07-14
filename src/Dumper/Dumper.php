<?php declare(strict_types=1);

namespace Kirameki\Dumper;

class Dumper
{
    /**
     * @var Dumper|null
     */
    protected static ?self $instance = null;

    /**
     * @param Dumper|null $instance
     * @return self|null
     */
    public static function setInstance(?self $instance): ?self
    {
        self::$instance = $instance;
        return $instance;
    }

    public static function getInstance(): self
    {
        return self::$instance ??= new self();
    }

    /**
     * @return bool
     */
    public static function hasInstance(): bool
    {
        return self::$instance !== null;
    }

    /**
     * @param Config $config
     */
    public function __construct(
        protected Config $config = new Config(),
    )
    {
    }

    /**
     * @param mixed ...$vars
     * @return void
     */
    public function dump(mixed ...$vars): void
    {
        $config = $this->config;
        foreach ($vars as $var) {
            $format = $config->formatter->format($var);
            $config->writer->write($format);
        }
    }
}
