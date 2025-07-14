<?php declare(strict_types=1);

namespace Kirameki\Text;

class Utf8Object extends StrObject
{
    /**
     * Overridden to cache in this class
     */
    protected static Str $ref;

    /**
     * @param string $value
     */
    public function __construct(string $value = '')
    {
        static::$ref ??= new Utf8();
        parent::__construct($value);
    }

    /**
     * @return int
     */
    public function byteLength(): int
    {
        return Utf8::byteLength($this->value);
    }

    /**
     * @param int $position
     * @param string $ellipsis
     * @return static
     */
    public function cut(int $position, string $ellipsis = ''): static
    {
        return new static(Utf8::cut($this->value, $position, $ellipsis));
    }
}
