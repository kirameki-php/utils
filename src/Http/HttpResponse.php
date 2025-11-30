<?php declare(strict_types=1);

namespace Kirameki\Http;

use function implode;

class HttpResponse
{
    /**
     * @param float $version
     * @param int $statusCode
     * @param HttpResponseHeaders $headers
     * @param HttpBody $body
     */
    public function __construct(
        public readonly float $version,
        public readonly int $statusCode,
        public readonly HttpResponseHeaders $headers,
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
            $this->protocolVersion(),
            $this->statusCode,
            StatusCode::asPhrase($this->statusCode),
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
        $this->headers = clone $this->headers;
    }
}
