<?php declare(strict_types=1);

namespace Kirameki\Http;

use function implode;

class HttpRequest
{
    /**
     * @param HttpMethod $method
     * @param float $version
     * @param Url $url
     * @param HttpRequestHeaders $headers
     * @param HttpBody $body
     */
    public function __construct(
        public readonly HttpMethod $method,
        public readonly float $version,
        public readonly Url $url,
        public readonly HttpRequestHeaders $headers,
        public readonly HttpBody $body = new HttpBody(),
    ) {
    }

    /**
     * @return string
     */
    public function protocolVersion(): string
    {
        return "HTTP/{$this->version}";
    }

    /**
     * @return string
     */
    public function toString(): string
    {
        $crlf = "\r\n";

        $raw = implode(' ', [
            $this->method->value,
            $this->url->path,
            $this->protocolVersion(),
        ]) . $crlf;

        if ($headers = $this->headers->toString()) {
            $raw .= $headers . $crlf;
        }

        $raw .= $crlf;
        $raw .= $this->body->toString();
        $raw .= $crlf;

        return $raw;
    }

    /**
     * @return void
     */
    public function __clone()
    {
        // @phpstan-ignore property.readOnlyAssignNotInConstructor
        $this->url = clone $this->url;
        // @phpstan-ignore property.readOnlyAssignNotInConstructor
        $this->headers = clone $this->headers;
    }
}
