<?php declare(strict_types=1);

namespace Tests\Kirameki\Http;

use Kirameki\Http\Url;
use Kirameki\Testing\TestCase;

final class UrlTest extends TestCase
{
    public function test_parse(): void
    {
        $uri = Url::parse('https://user:pass@host:8080/path?query#fragment');
        $this->assertSame('https', $uri->scheme);
        $this->assertSame('user', $uri->username);
        $this->assertSame('pass', $uri->password);
        $this->assertSame('host', $uri->hostname);
        $this->assertSame(8080, $uri->port);
        $this->assertSame('/path', $uri->path);
        $this->assertSame('query', $uri->query);
        $this->assertSame('fragment', $uri->fragment);
        $this->assertSame('user:pass', $uri->userinfo);
        $this->assertSame('user:pass@host:8080', $uri->authority);
    }

    public function test__construct_with_ordered_parameters(): void
    {
        $uri = new Url('https', 'h', 1, 'p', 'q', 'f', 'u', 'p');
        $this->assertSame('https', $uri->scheme);
        $this->assertSame('h', $uri->hostname);
        $this->assertSame(1, $uri->port);
        $this->assertSame('p', $uri->path);
        $this->assertSame('q', $uri->query);
        $this->assertSame('f', $uri->fragment);
        $this->assertSame('u', $uri->username);
        $this->assertSame('p', $uri->password);
    }

    public function test_protocol_property(): void
    {
        $uri = new Url('https', 'example.com');
        $this->assertSame('https:', $uri->protocol);

        $uri = new Url('http', 'example.com');
        $this->assertSame('http:', $uri->protocol);

        $uri = new Url('ftp', 'example.com');
        $this->assertSame('ftp:', $uri->protocol);
    }

    public function test_host_property(): void
    {
        $uri = new Url('https', 'example.com');
        $this->assertSame('example.com', $uri->host);

        $uri = new Url('https', 'example.com', 8080);
        $this->assertSame('example.com:8080', $uri->host);

        $uri = new Url('http', 'localhost', 3000);
        $this->assertSame('localhost:3000', $uri->host);
    }

    public function test_host_property_without_port(): void
    {
        $uri = new Url('https', 'example.com', null);
        $this->assertSame('example.com', $uri->host);
    }

    public function test_origin_property(): void
    {
        $uri = new Url('https', 'example.com');
        $this->assertSame('https://example.com', $uri->origin);

        $uri = new Url('https', 'example.com', 8080);
        $this->assertSame('https://example.com:8080', $uri->origin);

        $uri = new Url('http', 'localhost', 3000);
        $this->assertSame('http://localhost:3000', $uri->origin);
    }

    public function test_userinfo_property(): void
    {
        $uri = new Url('https', 'example.com', null, '', '', '', 'user', 'pass');
        $this->assertSame('user:pass', $uri->userinfo);

        $uri = new Url('https', 'example.com', null, '', '', '', 'user', '');
        $this->assertSame('user', $uri->userinfo);

        $uri = new Url('https', 'example.com', null, '', '', '', 'user');
        $this->assertSame('user', $uri->userinfo);

        $uri = new Url('https', 'example.com');
        $this->assertSame('', $uri->userinfo);
    }

    public function test_authority_property(): void
    {
        $uri = new Url('https', 'example.com', 8080, '', '', '', 'user', 'pass');
        $this->assertSame('user:pass@example.com:8080', $uri->authority);

        $uri = new Url('https', 'example.com', null, '', '', '', 'user');
        $this->assertSame('user@example.com', $uri->authority);

        $uri = new Url('https', 'example.com');
        $this->assertSame('example.com', $uri->authority);
    }

    public function test_parseQuery(): void
    {
        $uri = new Url('https', 'example.com', null, '', 'key1=value1&key2=value2&key3');
        $expected = [
            'key1' => 'value1',
            'key2' => 'value2',
            'key3' => '',
        ];
        $this->assertSame($expected, $uri->parseQuery());
    }

    public function test_parseQuery_empty(): void
    {
        $uri = new Url('https', 'example.com', null, '', '');
        $this->assertSame([], $uri->parseQuery());
    }

    public function test_parseQuery_with_arrays(): void
    {
        $uri = new Url('https', 'example.com', null, '', 'arr[]=1&arr[]=2&obj[key]=value');
        $expected = [
            'arr' => ['1', '2'],
            'obj' => ['key' => 'value'],
        ];
        $this->assertSame($expected, $uri->parseQuery());
    }

    public function test_getProtocolComponent(): void
    {
        $uri = new Url('https', 'example.com');
        $this->assertSame('https://', $uri->getProtocolComponent());

        $uri = new Url('ftp', 'example.com');
        $this->assertSame('ftp://', $uri->getProtocolComponent());
    }

    public function test_getUserInfoComponent(): void
    {
        $uri = new Url('https', 'example.com', null, '', '', '', 'user', 'pass');
        $this->assertSame('user:pass@', $uri->getUserInfoComponent());

        $uri = new Url('https', 'example.com', null, '', '', '', 'user');
        $this->assertSame('user@', $uri->getUserInfoComponent());

        $uri = new Url('https', 'example.com');
        $this->assertSame('', $uri->getUserInfoComponent());
    }

    public function test_getQueryComponent(): void
    {
        $uri = new Url('https', 'example.com', null, '', 'key=value');
        $this->assertSame('?key=value', $uri->getQueryComponent());

        $uri = new Url('https', 'example.com', null, '', '');
        $this->assertSame('', $uri->getQueryComponent());
    }

    public function test_getFragmentComponent(): void
    {
        $uri = new Url('https', 'example.com', null, '', '', 'section1');
        $this->assertSame('#section1', $uri->getFragmentComponent());

        $uri = new Url('https', 'example.com', null, '', '', '');
        $this->assertSame('', $uri->getFragmentComponent());
    }

    public function test_toString(): void
    {
        $uri = new Url('https', 'example.com', 8080, '/path', 'key=value', 'fragment', 'user', 'pass');
        $expected = 'https://user:pass@example.com:8080/path?key=value#fragment';
        $this->assertSame($expected, $uri->toString());
        $this->assertSame($expected, (string) $uri);
    }

    public function test_toString_minimal(): void
    {
        $uri = new Url('https', 'example.com');
        $expected = 'https://example.com';
        $this->assertSame($expected, $uri->toString());
        $this->assertSame($expected, (string) $uri);
    }

    public function test_toString_with_path_only(): void
    {
        $uri = new Url('https', 'example.com', null, '/path/to/resource');
        $expected = 'https://example.com/path/to/resource';
        $this->assertSame($expected, $uri->toString());
    }

    public function test_parse_with_defaults(): void
    {
        $uri = Url::parse('https://example.com/path');
        $this->assertSame('https', $uri->scheme);
        $this->assertSame('example.com', $uri->hostname);
        $this->assertSame('/path', $uri->path);
        $this->assertSame('', $uri->query);
        $this->assertSame('', $uri->fragment);
        $this->assertSame('', $uri->username);
        $this->assertSame('', $uri->password);
    }

    public function test_parse_minimal_url(): void
    {
        $uri = Url::parse('https://example.com');
        $this->assertSame('https', $uri->scheme);
        $this->assertSame('example.com', $uri->hostname);
        $this->assertNull($uri->port);
        $this->assertSame('', $uri->path);
        $this->assertSame('', $uri->query);
        $this->assertSame('', $uri->fragment);
        $this->assertSame('', $uri->username);
        $this->assertSame('', $uri->password);
    }

    public function test_edge_cases_userinfo(): void
    {
        // Test with empty password
        $uri = new Url('https', 'example.com', null, '', '', '', 'user', '');
        $this->assertSame('user', $uri->userinfo);
        $this->assertSame('user@', $uri->getUserInfoComponent());

        // Test with only username
        $uri = new Url('https', 'example.com', null, '', '', '', 'user');
        $this->assertSame('user', $uri->userinfo);
        $this->assertSame('user@', $uri->getUserInfoComponent());

        // Test with empty username but password (edge case)
        $uri = new Url('https', 'example.com', null, '', '', '', '', 'pass');
        $this->assertSame(':pass', $uri->userinfo);
        $this->assertSame(':pass@', $uri->getUserInfoComponent());
    }

    public function test_component_methods_edge_cases(): void
    {
        // Test with empty query
        $uri = new Url('https', 'example.com', null, '', '');
        $this->assertSame('', $uri->getQueryComponent());

        // Test with empty fragment
        $uri = new Url('https', 'example.com', null, '', '', '');
        $this->assertSame('', $uri->getFragmentComponent());

        // Test with just question mark in query (from parsing)
        $uri = Url::parse('https://example.com/?');
        $this->assertSame('', $uri->getQueryComponent());

        // Test with just hash in fragment (from parsing)
        $uri = Url::parse('https://example.com/#');
        $this->assertSame('', $uri->getFragmentComponent());
    }
}
