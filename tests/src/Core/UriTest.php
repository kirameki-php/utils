<?php declare(strict_types=1);

namespace Tests\Kirameki\Core;

use Kirameki\Core\Uri;
use Kirameki\Testing\TestCase;

final class UriTest extends TestCase
{
    public function test_parse(): void
    {
        $uri = Uri::parse('https://user:pass@host:8080/path?query#fragment');
        $this->assertSame('https', $uri->scheme);
        $this->assertSame('user', $uri->user);
        $this->assertSame('pass', $uri->pass);
        $this->assertSame('host', $uri->host);
        $this->assertSame(8080, $uri->port);
        $this->assertSame('/path', $uri->path);
        $this->assertSame('query', $uri->query);
        $this->assertSame('fragment', $uri->fragment);
        $this->assertSame('user:pass', $uri->userinfo());
        $this->assertSame('user:pass@host:8080', $uri->authority());
    }

    public function test__construct_with_ordered_parameters(): void
    {
        $uri = new Uri('https', 'h', 1, 'p', 'q', 'f', 'u', 'p');
        $this->assertSame('https', $uri->scheme);
        $this->assertSame('h', $uri->host);
        $this->assertSame(1, $uri->port);
        $this->assertSame('p', $uri->path);
        $this->assertSame('q', $uri->query);
        $this->assertSame('f', $uri->fragment);
        $this->assertSame('u', $uri->user);
        $this->assertSame('p', $uri->pass);
    }

    public function test__construct_with_named_parameters(): void
    {
        $uri = new Uri(
            scheme: 'https',
            host: 'h',
            port: 1,
            path: 'p',
            query: 'q',
            fragment: 'f',
            user: 'u',
            pass: 'p',
        );
        $this->assertSame('https', $uri->scheme);
        $this->assertSame('h', $uri->host);
        $this->assertSame(1, $uri->port);
        $this->assertSame('p', $uri->path);
        $this->assertSame('q', $uri->query);
        $this->assertSame('f', $uri->fragment);
        $this->assertSame('u', $uri->user);
        $this->assertSame('p', $uri->pass);
    }

    public function test_userinfo(): void
    {
        $uri = new Uri('https', 'h', 1, 'p', 'q', 'f', 'u', 'p');
        $this->assertSame('u:p', $uri->userinfo());

        $uri = new Uri('https', 'h', 1, 'p', 'q', 'f', 'u');
        $this->assertSame('u', $uri->userinfo());

        $uri = new Uri('https', 'h', 1, 'p', 'q', 'f');
        $this->assertSame('', $uri->userinfo());
    }

    public function test_authority(): void
    {
        $uri = new Uri('https', 'h', 1, 'p', 'q', 'f', 'u', 'p');
        $this->assertSame('u:p@h:1', $uri->authority());

        $uri = new Uri('https', 'h', 1, 'p', 'q', 'f', 'u');
        $this->assertSame('u@h:1', $uri->authority());

        $uri = new Uri('https', 'h', 1, 'p', 'q', 'f');
        $this->assertSame('h:1', $uri->authority());

        $uri = new Uri('https', 'h', null, 'p', 'q', 'f');
        $this->assertSame('h', $uri->authority());
    }

    public function test_authority_with_empty_host(): void
    {
        $uri = new Uri('https', '', 1, 'p', 'q', 'f', 'u', 'p');
        $this->assertSame('u:p@', $uri->authority());

        $uri = new Uri('https', '', 1, 'p', 'q', 'f', 'u');
        $this->assertSame('u@', $uri->authority());

        $uri = new Uri('https', '', 1, 'p', 'q', 'f');
        $this->assertSame('', $uri->authority());
    }

    public function test_queryParameters(): void
    {
        $uri = Uri::parse('https://user:pass@host:8080/path?query#fragment');
        $this->assertSame(['query' => ''], $uri->queryParameters());

        $uri = Uri::parse('https://user:pass@host:8080/path#fragment');
        $this->assertSame([], $uri->queryParameters());

        $uri = Uri::parse('https://user:pass@host:8080/path?');
        $this->assertSame([], $uri->queryParameters());

        $uri = Uri::parse('https://user:pass@host:8080/path');
        $this->assertSame([], $uri->queryParameters());

        $uri = Uri::parse('https://user:pass@host:8080/path?k1=v1&k2=v2');
        $this->assertSame(['k1' => 'v1', 'k2' => 'v2'], $uri->queryParameters());
    }

    public function test_pathAndQuery(): void
    {
        $uri = Uri::parse('https://user:pass@host:8080/path?query#fragment');
        $this->assertSame('/path?query', $uri->pathAndQuery());

        $uri = Uri::parse('https://user:pass@host:8080/path#fragment');
        $this->assertSame('/path', $uri->pathAndQuery());

        $uri = Uri::parse('https://user:pass@host:8080/path?');
        $this->assertSame('/path', $uri->pathAndQuery());

        $uri = Uri::parse('https://user:pass@host:8080/path');
        $this->assertSame('/path', $uri->pathAndQuery());

        $uri = Uri::parse('https://user:pass@host:8080');
        $this->assertNull($uri->pathAndQuery());
    }

    public function test_toString(): void
    {
        $uri = Uri::parse('https://user:pass@host:8080/path?query#fragment');
        $this->assertSame('https://user:pass@host:8080/path?query#fragment', $uri->toString());

        $uri = Uri::parse('https://host');
        $this->assertSame('https://host', $uri->toString());

        $uri = Uri::parse('https://host:8080');
        $this->assertSame('https://host:8080', $uri->toString());

        $uri = Uri::parse('https://host/path');
        $this->assertSame('https://host/path', $uri->toString());

        $uri = Uri::parse('https://host/path?query');
        $this->assertSame('https://host/path?query', $uri->toString());

        $uri = Uri::parse('https://host/path#fragment');
        $this->assertSame('https://host/path#fragment', $uri->toString());
    }

    public function test___toString(): void
    {
        $uri = Uri::parse('https://user:pass@host:8080/path?query#fragment');
        $this->assertSame('https://user:pass@host:8080/path?query#fragment', (string)$uri);
    }
}
