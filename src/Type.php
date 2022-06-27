<?php declare(strict_types=1);

namespace Kirameki\Utils;

use Closure;
use UnitEnum;
use function is_array;
use function is_bool;
use function is_float;
use function is_int;
use function is_null;
use function is_object;
use function is_resource;
use function is_string;

class Type
{
    /**
     * @param mixed $value
     * @return string
     */
    public static function of(mixed $value): string
    {
        if (is_null($value)) {
            return 'null';
        }

        if (is_bool($value)) {
            return 'bool';
        }

        if (is_int($value)) {
            return 'int';
        }

        if (is_float($value)) {
            return 'float';
        }

        if (is_string($value)) {
            return 'string';
        }

        if (is_array($value)) {
            return 'array';
        }

        if ($value instanceof UnitEnum) {
            return 'enum';
        }

        if ($value instanceof Closure) {
            return 'closure';
        }

        if (is_object($value)) {
            return 'object';
        }

        if (is_resource($value)) {
            return "resource";
        }

        // @codeCoverageIgnoreStart
        return "unknown type";
        // @codeCoverageIgnoreEnd
    }
}
