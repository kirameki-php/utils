<?php declare(strict_types=1);

namespace Kirameki\Utils;

use function json_decode;
use function json_encode;

class Json
{
    /**
     * Encode data as JSON string.
     *
     * Example:
     * ```php
     * Json::encode(true); // 'true'
     * Json::encode(['a' => 1]); // '{"a":1}'
     * Json::encode(['a' => 1], true); // "{\n    "a": 1\n}"
     * ```
     *
     * @param mixed $data
     * The data being encoded. String data must be UTF-8 encoded.
     * @param bool $formatted
     * Format JSON in a human-readable format.
     * @return string
     * JSON encoded string.
     */
    public static function encode(mixed $data, bool $formatted = false): string
    {
        $options = JSON_PRESERVE_ZERO_FRACTION |
                   JSON_UNESCAPED_UNICODE |
                   JSON_UNESCAPED_SLASHES;

        if ($formatted) {
            $options |= JSON_PRETTY_PRINT;
        }

        return json_encode($data, $options | JSON_THROW_ON_ERROR);
    }

    /**
     * Decodes a JSON string.
     *
     * Example:
     * ```php
     * Json::decode('true'); // true
     * Json::decode('{"a":1}'); // ['a' => 1]
     * ```
     *
     * @param string $json
     * The value being decoded. Must be a valid UTF-8 encoded string.
     * @return mixed
     * Decoded data.
     */
    public static function decode(string $json): mixed
    {
        return json_decode($json, true, 512, JSON_THROW_ON_ERROR);
    }
}
