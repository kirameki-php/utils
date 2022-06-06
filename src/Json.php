<?php declare(strict_types=1);

namespace Kirameki\Utils;

use function json_decode;
use function json_encode;

class Json
{
    protected static int $encodeOptions =
        JSON_PRESERVE_ZERO_FRACTION |
        JSON_UNESCAPED_UNICODE |
        JSON_UNESCAPED_SLASHES;

    /**
     * @param mixed $data
     * @param bool $formatted
     * @return string
     */
    public static function encode(mixed $data, bool $formatted = false): string
    {
        $options = static::$encodeOptions;
        if ($formatted) {
            $options |= JSON_PRETTY_PRINT;
        }
        return json_encode($data, $options | JSON_THROW_ON_ERROR);
    }

    /**
     * @param string $json
     * @return mixed
     */
    public static function decode(string $json): mixed
    {
        return json_decode($json, true, 512, JSON_THROW_ON_ERROR);
    }
}
