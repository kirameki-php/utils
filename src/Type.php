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
     * Check the type of the given value.
     * The types this will return is below:
     * - `null`
     * - `bool`
     * - `int`
     * - `float`
     * - `string`
     * - `array`
     * - `enum`
     * - `closure`
     * - `object`
     * - `resource`
     *
     * Example:
     * ```php
     * Type::of(null); // 'null'
     * Type::of(true); // 'bool'
     * Type::of(1); // 'int'
     * Type::of(1.0); // 'float'
     * Type::of('abc'); // 'string'
     * Type::of([1, 2, 3]); // 'array'
     * Type::of(new stdClass()); // 'object'
     * Type::of(fn() => true); // 'closure'
     * ```
     *
     * @param mixed $value
     * Value to be evaluated.
     * @return string
     * Returns the type name as string.
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
            return 'resource';
        }

        // @codeCoverageIgnoreStart
        return "unknown type";
        // @codeCoverageIgnoreEnd
    }
}
