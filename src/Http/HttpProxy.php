<?php declare(strict_types=1);

namespace Kirameki\Http;

class HttpProxy
{
    /**
     * @param Url $url
     */
    public function __construct(
        public readonly Url $url,
    ) {
    }
}
