<?php declare(strict_types=1);

namespace Tests\Kirameki\Http;

use Kirameki\Http\StatusCode;
use Kirameki\Testing\TestCase;

final class StatusCodeTest extends TestCase
{
    public function test_asPhrase(): void
    {
        $this->assertSame('OK', StatusCode::asPhrase(StatusCode::OK));
        $this->assertSame('Created', StatusCode::asPhrase(StatusCode::Created));
        $this->assertSame('Accepted', StatusCode::asPhrase(StatusCode::Accepted));
        $this->assertSame('Non-Authoritative Information', StatusCode::asPhrase(StatusCode::NonAuthoritativeInformation));
        $this->assertSame('No Content', StatusCode::asPhrase(StatusCode::NoContent));
        $this->assertSame('Reset Content', StatusCode::asPhrase(StatusCode::ResetContent));
        $this->assertSame('Partial Content', StatusCode::asPhrase(StatusCode::PartialContent));
        $this->assertSame('Multi-Status', StatusCode::asPhrase(StatusCode::MultiStatus));
        $this->assertSame('Already Reported', StatusCode::asPhrase(StatusCode::AlreadyReported));
        $this->assertSame('IM Used', StatusCode::asPhrase(StatusCode::ImUsed));
        $this->assertSame('Multiple Choices', StatusCode::asPhrase(StatusCode::MultipleChoices));
        $this->assertSame('Moved Permanently', StatusCode::asPhrase(StatusCode::MovedPermanently));
        $this->assertSame('Found', StatusCode::asPhrase(StatusCode::Found));
        $this->assertSame('See Other', StatusCode::asPhrase(StatusCode::SeeOther));
        $this->assertSame('Not Modified', StatusCode::asPhrase(StatusCode::NotModified));
        $this->assertSame('Use Proxy', StatusCode::asPhrase(StatusCode::UseProxy));
        $this->assertSame('Switch Proxy', StatusCode::asPhrase(StatusCode::SwitchProxy));
        $this->assertSame('Temporary Redirect', StatusCode::asPhrase(StatusCode::TemporaryRedirect));
        $this->assertSame('Permanent Redirect', StatusCode::asPhrase(StatusCode::PermanentRedirect));
        $this->assertSame('Bad Request', StatusCode::asPhrase(StatusCode::BadRequest));
        $this->assertSame('Unauthorized', StatusCode::asPhrase(StatusCode::Unauthorized));
        $this->assertSame('Payment Required', StatusCode::asPhrase(StatusCode::PaymentRequired));
        $this->assertSame('Forbidden', StatusCode::asPhrase(StatusCode::Forbidden));
        $this->assertSame('Not Found', StatusCode::asPhrase(StatusCode::NotFound));
        $this->assertSame('Method Not Allowed', StatusCode::asPhrase(StatusCode::MethodNotAllowed));
        $this->assertSame('Not Acceptable', StatusCode::asPhrase(StatusCode::NotAcceptable));
        $this->assertSame('Proxy Authentication Required', StatusCode::asPhrase(StatusCode::ProxyAuthenticationRequired));
        $this->assertSame('Request Timeout', StatusCode::asPhrase(StatusCode::RequestTimeout));
        $this->assertSame('Conflict', StatusCode::asPhrase(StatusCode::Conflict));
        $this->assertSame('Gone', StatusCode::asPhrase(StatusCode::Gone));
        $this->assertSame('Length Required', StatusCode::asPhrase(StatusCode::LengthRequired));
        $this->assertSame('Precondition Failed', StatusCode::asPhrase(StatusCode::PreconditionFailed));
        $this->assertSame('Payload Too Large', StatusCode::asPhrase(StatusCode::PayloadTooLarge));
        $this->assertSame('URI Too Long', StatusCode::asPhrase(StatusCode::UriTooLong));
        $this->assertSame('Unsupported Media Type', StatusCode::asPhrase(StatusCode::UnsupportedMediaType));
        $this->assertSame('Range Not Satisfiable', StatusCode::asPhrase(StatusCode::RangeNotSatisfiable));
        $this->assertSame('Expectation Failed', StatusCode::asPhrase(StatusCode::ExpectationFailed));
        $this->assertSame('I\'m a teapot', StatusCode::asPhrase(StatusCode::ImATeaPot));
        $this->assertSame('Misdirected Request', StatusCode::asPhrase(StatusCode::MisdirectedRequest));
        $this->assertSame('Unprocessable Entity', StatusCode::asPhrase(StatusCode::UnprocessableEntity));
        $this->assertSame('Locked', StatusCode::asPhrase(StatusCode::Locked));
        $this->assertSame('Failed Dependency', StatusCode::asPhrase(StatusCode::FailedDependency));
        $this->assertSame('Too Early', StatusCode::asPhrase(StatusCode::TooEarly));
        $this->assertSame('Upgrade Required', StatusCode::asPhrase(StatusCode::UpgradeRequired));
        $this->assertSame('Precondition Required', StatusCode::asPhrase(StatusCode::PreconditionRequired));
        $this->assertSame('Too Many Requests', StatusCode::asPhrase(StatusCode::TooManyRequests));
        $this->assertSame('Request Header Fields Too Large', StatusCode::asPhrase(StatusCode::RequestHeaderFieldsTooLarge));
        $this->assertSame('Unavailable For Legal Reasons', StatusCode::asPhrase(StatusCode::UnavailableForLegalReasons));
        $this->assertSame('Internal Server Error', StatusCode::asPhrase(StatusCode::InternalServerError));
        $this->assertSame('Not Implemented', StatusCode::asPhrase(StatusCode::NotImplemented));
        $this->assertSame('Bad Gateway', StatusCode::asPhrase(StatusCode::BadGateway));
        $this->assertSame('Service Unavailable', StatusCode::asPhrase(StatusCode::ServiceUnavailable));
        $this->assertSame('Gateway Timeout', StatusCode::asPhrase(StatusCode::GatewayTimeout));
        $this->assertSame('HTTP Version Not Supported', StatusCode::asPhrase(StatusCode::HttpVersionNotSupported));
        $this->assertSame('Variant Also Negotiates', StatusCode::asPhrase(StatusCode::VariantAlsoNegotiates));
        $this->assertSame('Insufficient Storage', StatusCode::asPhrase(StatusCode::InsufficientStorage));
        $this->assertSame('Loop Detected', StatusCode::asPhrase(StatusCode::LoopDetected));
        $this->assertSame('Not Extended', StatusCode::asPhrase(StatusCode::NotExtended));
        $this->assertSame('Network Authentication Required', StatusCode::asPhrase(StatusCode::NetworkAuthenticationRequired));
    }
}
