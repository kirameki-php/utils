<?php declare(strict_types=1);

namespace Kirameki\Http;

class HttpRequestBuilder
{
    /**
     * @param HttpMethod $method
     * @param string|Url $url
     * @param float $version
     * @param HttpRequestHeaders $headers
     * @param HttpRequestBody $body
     */
    public function __construct(
        public readonly HttpMethod $method,
        public readonly string|Url $url,
        public float $version = 1.1,
        public HttpRequestHeaders $headers = new HttpRequestHeaders(),
        public HttpRequestBody $body = new HttpRequestBody(),
    ) {
    }

    /**
     * @param float $version
     * @return $this
     */
    public function setVersion(float $version): static
    {
        $this->version = $version;
        return $this;
    }

    public function addHeader(string $name, string $value): static
    {
        $this->headers->add($name, $value);
        return $this;
    }

    /**
     * @param iterable<string, string> $headers
     * @return $this
     */
    public function addHeaders(iterable $headers): static
    {
        foreach ($headers as $name => $value) {
            $this->headers->add($name, $value);
        }
        return $this;
    }

    /**
     * @param HttpRequestBody $body
     * @return $this
     */
    public function setBody(HttpRequestBody $body): static
    {
        $this->body = $body;
        return $this;
    }

    /**
     * @return HttpRequest
     */
    public function build(): HttpRequest
    {
        return new HttpRequest(
            $this->method,
            $this->version,
            $this->url instanceof Url ? $this->url : Url::parse($this->url),
            $this->headers,
            $this->body,
        );
    }
}
