<?php declare(strict_types=1);

namespace Kirameki\Http;

use function assert;
use function implode;
use function parse_url;

class Url
{
    /**
     * @param string $uri
     * @return static
     */
    public static function parse(string $uri): self
    {
        $parsed = parse_url($uri);
        assert($parsed !== false, 'Invalid URI: ' . $uri);
        return new static(
            scheme: $parsed['scheme'] ?? 'http',
            hostname: $parsed['host'] ?? 'localhost',
            port: $parsed['port'] ?? null,
            path: $parsed['path'] ?? '',
            query: $parsed['query'] ?? '',
            fragment: $parsed['fragment'] ?? '',
            username: $parsed['user'] ?? '',
            password: $parsed['pass'] ?? '',
        );
    }

    /**
     * @var string
     */
    public string $protocol {
        get => "{$this->scheme}:";
    }

    /**
     * @return string
     */
    public string $userinfo {
        get => $this->password !== '' ? "{$this->username}:{$this->password}" : $this->username;
    }

    /**
     * @return string
     */
    public string $host {
        get => $this->hostname . ($this->port !== null ? ":{$this->port}" : '');
    }

    /**
     * @return string
     */
    public string $authority {
        get => $this->getUserInfoComponent() . $this->host;
    }

    /**
     * @return string
     */
    public string $origin {
        get => $this->protocol . '//' . $this->host;
    }

    /**
     * @param string $scheme
     * @param string $hostname
     * @param int $port
     * @param string $path
     * @param string $query
     * @param string $fragment
     * @param string $username
     * @param string $password
     */
    public function __construct(
        public readonly string $scheme,
        public readonly string $hostname,
        public readonly ?int $port = null,
        public readonly string $path = '',
        public readonly string $query = '',
        public readonly string $fragment = '',
        public readonly string $username = '',
        public readonly string $password = '',
    ) {
    }

    /**
     * @return array<array<string, mixed>|string>
     */
    public function parseQuery(): array
    {
        $result = [];
        parse_str($this->query, $result);
        return $result;
    }

    /**
     * @return string
     */
    public function getProtocolComponent(): string
    {
        return "{$this->protocol}//";
    }

    /**
     * @return string
     */
    public function getUserInfoComponent(): string
    {
        return $this->userinfo !== '' ? "{$this->userinfo}@" : '';
    }

    /**
     * @return string
     */
    public function getQueryComponent(): string
    {
        return $this->query !== '' ? "?{$this->query}" : '';
    }

    /**
     * @return string
     */
    public function getFragmentComponent(): string
    {
        return $this->fragment !== '' ? "#{$this->fragment}" : '';
    }

    /**
     * @return string
     */
    public function toString(): string
    {
        return $this->__toString();
    }

    /**
     * @return string
     */
    public function __toString(): string
    {
        return implode('', [
            $this->getProtocolComponent(),
            $this->getUserInfoComponent(),
            $this->host,
            $this->path,
            $this->getQueryComponent(),
            $this->getFragmentComponent(),
        ]);
    }
}
