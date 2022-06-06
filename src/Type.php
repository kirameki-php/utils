<?php declare(strict_types=1);

namespace Kirameki\Utils;

use DateTimeInterface;
use LogicException;
use Traversable;
use UnitEnum;
use function get_resource_type;
use function in_array;
use function is_array;
use function is_bool;
use function is_float;
use function is_int;
use function is_null;
use function is_numeric;
use function is_object;
use function is_resource;
use function is_string;
use function iterator_to_array;
use function mb_strcut;
use function spl_object_hash;
use function strtolower;

class Type
{
    /**
     * Cast string to a more fitting type.
     *
     * @param string $string
     * @return bool|float|int|string|null
     */
    public static function detect(string $string): bool|float|int|string|null
    {

        if (is_numeric($string)) {
            // Use Identity operator to cast to int/float.
            // @see https://www.php.net/manual/en/language.operators.arithmetic.php
            return +$string;
        }

        $lowered = strtolower($string);

        if ($lowered === 'null') {
            return null;
        }

        if (in_array($lowered, ['true', 'false'], true)) {
            return $lowered === 'true';
        }

        return $string;
    }

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

        if ($value instanceof DateTimeInterface) {
            return 'datetime';
        }

        if ($value instanceof UnitEnum) {
            return 'enum';
        }

        if (is_object($value)) {
            return 'object';
        }

        if (is_resource($value)) {
            return "resource";
        }

        return "unknown type";
    }

    /**
     * @param mixed $value
     * @return string
     */
    public static function dump(mixed $value): string
    {
        if (is_null($value)) {
            return 'null';
        }

        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }

        if (is_int($value)) {
            return (string) $value;
        }

        if (is_float($value)) {
            $str = (string) $value;
            return str_contains($str, '.')
                ? $str
                : $str . '.0';
        }

        if (is_string($value)) {
            return '"' . Str::wrap($value, 0, 1000) . '"';
        }

        if (is_array($value)) {
            return Json::encode($value);
        }

        if ($value instanceof Traversable) {
            return Json::encode(iterator_to_array($value));
        }

        if ($value instanceof DateTimeInterface) {
            return $value->format(DATE_RFC3339_EXTENDED);
        }

        if ($value instanceof UnitEnum) {
            return $value::class . '::' . $value->name;
        }

        if (is_object($value)) {
            return $value::class . ':' . spl_object_hash($value);
        }

        if (is_resource($value)) {
            return get_resource_type($value);
        }

        throw new LogicException('Unknown type: ' . $value);
    }

}
