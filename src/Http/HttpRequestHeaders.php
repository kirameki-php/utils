<?php

namespace Kirameki\Http;

class HttpRequestHeaders extends HttpHeaders
{
    // Common Headers
    public const string ACCEPT = 'Accept';
    public const string ACCEPT_ENCODING = 'Accept-Encoding';
    public const string ACCEPT_LANGUAGE = 'Accept-Language';
    public const string AUTHORIZATION = 'Authorization';
    public const string CACHE_CONTROL = 'Cache-Control';
    public const string CONNECTION = 'Connection';
    public const string CONTENT_ENCODING = 'Content-Encoding';
    public const string CONTENT_LENGTH = 'Content-Length';
    public const string CONTENT_TYPE = 'Content-Type';
    public const string COOKIE = 'Cookie';
    public const string DATE = 'Date';
    public const string HOST = 'Host';
    public const string RANGE = 'Range';
    public const string REFERER = 'Referer';
    public const string TE = 'TE';
    public const string UPGRADE_INSECURE_REQUESTS = 'Upgrade-Insecure-Requests';
    public const string USER_AGENT = 'User-Agent';
    public const string VARY = 'Vary';
    public const string VIA = 'Via';
    public const string X_FORWARDED_FOR = 'X-Forwarded-For';
    public const string X_FORWARDED_HOST = 'X-Forwarded-Host';
    public const string X_FORWARDED_PROTO = 'X-Forwarded-Proto';
}
