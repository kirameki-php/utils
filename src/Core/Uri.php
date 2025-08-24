<?php declare(strict_types=1);

namespace Kirameki\Core;

use function assert;
use function parse_url;

class Uri
{
    /**
     * @param string $url
     * @return static
     */
    public static function parse(string $url): self
    {
        $parsed = parse_url($url);
        assert($parsed !== false, 'Invalid URL');
        return new static(...$parsed);
    }

    /**
     * @param string $scheme
     * @param string $host
     * @param int $port
     * @param string $path
     * @param string $query
     * @param string $fragment
     * @param string $user
     * @param string $pass
     */
    public function __construct(
        public readonly string $scheme,
        public readonly string $host,
        public readonly ?int $port = null,
        public readonly string $path = '',
        public readonly string $query = '',
        public readonly string $fragment = '',
        public readonly string $user = '',
        public readonly string $pass = '',
    ) {
    }

    /**
     * @return string
     */
    public function userinfo(): string
    {
        $userinfo = $this->user;
        if ($userinfo !== '' && $this->pass !== '') {
            $userinfo.= ':'.$this->pass;
        }
        return $userinfo;
    }

    /**
     * @return string
     */
    public function authority(): string
    {
        $host = $this->host;
        $port = $this->port;
        $authority = $userinfo = $this->userinfo();
        $authority .= ($userinfo !== '') ? '@' . $host : $host;
        if ($port !== null && $host !== '') {
            $authority .= ':' . $port;
        }
        return $authority;
    }

    /**
     * @return array<array<string, mixed>|string>
     */
    public function queryParameters(): array
    {
        $result = [];
        parse_str($this->query, $result);
        return $result;
    }

    /**
     * @return string|null
     */
    public function pathAndQuery(): ?string
    {
        $str = null;
        $path = $this->path;
        if ($path !== '') {
            $str.= $path;
        }
        $query = $this->query;
        if ($query !== '') {
            $str.= '?'.$query;
        }
        return $str;
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
        $str = $this->scheme;
        $str.= '://'.$this->authority();
        $path = $this->path;
        if ($path !== '') {
            $str.= $path;
        }
        $query = $this->query;
        if ($query !== '') {
            $str.= '?'.$query;
        }
        $fragment = $this->fragment;
        if ($fragment !== '') {
            $str.= '#'.$fragment;
        }
        return $str;
    }
}
