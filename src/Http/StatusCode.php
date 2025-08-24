<?php declare(strict_types=1);

namespace Kirameki\Http;

class StatusCode
{
    public const int OK = 200;
    public const int Created = 201;
    public const int Accepted = 202;
    public const int NonAuthoritativeInformation = 203;
    public const int NoContent = 204;
    public const int ResetContent = 205;
    public const int PartialContent = 206;
    public const int MultiStatus = 207;
    public const int AlreadyReported = 208;
    public const int ImUsed = 226;
    public const int MultipleChoices = 300;
    public const int MovedPermanently = 301;
    public const int Found = 302;
    public const int SeeOther = 303;
    public const int NotModified = 304;
    public const int UseProxy = 305;
    public const int SwitchProxy = 306;
    public const int TemporaryRedirect = 307;
    public const int PermanentRedirect = 308;
    public const int BadRequest = 400;
    public const int Unauthorized = 401;
    public const int PaymentRequired = 402;
    public const int Forbidden = 403;
    public const int NotFound = 404;
    public const int MethodNotAllowed = 405;
    public const int NotAcceptable = 406;
    public const int ProxyAuthenticationRequired = 407;
    public const int RequestTimeout = 408;
    public const int Conflict = 409;
    public const int Gone = 410;
    public const int LengthRequired = 411;
    public const int PreconditionFailed = 412;
    public const int PayloadTooLarge = 413;
    public const int UriTooLong = 414;
    public const int UnsupportedMediaType = 415;
    public const int RangeNotSatisfiable = 416;
    public const int ExpectationFailed = 417;
    public const int ImATeaPot = 418;
    public const int MisdirectedRequest = 421;
    public const int UnprocessableEntity = 422;
    public const int Locked = 423;
    public const int FailedDependency = 424;
    public const int TooEarly = 425;
    public const int UpgradeRequired = 426;
    public const int PreconditionRequired = 428;
    public const int TooManyRequests = 429;
    public const int RequestHeaderFieldsTooLarge = 431;
    public const int UnavailableForLegalReasons = 451;
    public const int InternalServerError = 500;
    public const int NotImplemented = 501;
    public const int BadGateway = 502;
    public const int ServiceUnavailable = 503;
    public const int GatewayTimeout = 504;
    public const int HttpVersionNotSupported = 505;
    public const int VariantAlsoNegotiates = 506;
    public const int InsufficientStorage = 507;
    public const int LoopDetected = 508;
    public const int NotExtended = 510;
    public const int NetworkAuthenticationRequired = 511;

    /**
     * @param int $code
     * @return string
     */
    public static function asPhrase(int $code): string
    {
        return match ($code) {
            self::OK => 'OK',
            self::Created => 'Created',
            self::Accepted => 'Accepted',
            self::NonAuthoritativeInformation => 'Non-Authoritative Information',
            self::NoContent => 'No Content',
            self::ResetContent => 'Reset Content',
            self::PartialContent => 'Partial Content',
            self::MultiStatus => 'Multi-Status',
            self::AlreadyReported => 'Already Reported',
            self::ImUsed => 'IM Used',
            self::MultipleChoices => 'Multiple Choices',
            self::MovedPermanently => 'Moved Permanently',
            self::Found => 'Found',
            self::SeeOther => 'See Other',
            self::NotModified => 'Not Modified',
            self::UseProxy => 'Use Proxy',
            self::SwitchProxy => 'Switch Proxy',
            self::TemporaryRedirect => 'Temporary Redirect',
            self::PermanentRedirect => 'Permanent Redirect',
            self::BadRequest => 'Bad Request',
            self::Unauthorized => 'Unauthorized',
            self::PaymentRequired => 'Payment Required',
            self::Forbidden => 'Forbidden',
            self::NotFound => 'Not Found',
            self::MethodNotAllowed => 'Method Not Allowed',
            self::NotAcceptable => 'Not Acceptable',
            self::ProxyAuthenticationRequired => 'Proxy Authentication Required',
            self::RequestTimeout => 'Request Timeout',
            self::Conflict => 'Conflict',
            self::Gone => 'Gone',
            self::LengthRequired => 'Length Required',
            self::PreconditionFailed => 'Precondition Failed',
            self::PayloadTooLarge => 'Payload Too Large',
            self::UriTooLong => 'URI Too Long',
            self::UnsupportedMediaType => 'Unsupported Media Type',
            self::RangeNotSatisfiable => 'Range Not Satisfiable',
            self::ExpectationFailed => 'Expectation Failed',
            self::ImATeaPot => 'I\'m a teapot',
            self::MisdirectedRequest => 'Misdirected Request',
            self::UnprocessableEntity => 'Unprocessable Entity',
            self::Locked => 'Locked',
            self::FailedDependency => 'Failed Dependency',
            self::TooEarly => 'Too Early',
            self::UpgradeRequired => 'Upgrade Required',
            self::PreconditionRequired => 'Precondition Required',
            self::TooManyRequests => 'Too Many Requests',
            self::RequestHeaderFieldsTooLarge => 'Request Header Fields Too Large',
            self::UnavailableForLegalReasons => 'Unavailable For Legal Reasons',
            self::InternalServerError => 'Internal Server Error',
            self::NotImplemented => 'Not Implemented',
            self::BadGateway => 'Bad Gateway',
            self::ServiceUnavailable => 'Service Unavailable',
            self::GatewayTimeout => 'Gateway Timeout',
            self::HttpVersionNotSupported => 'HTTP Version Not Supported',
            self::VariantAlsoNegotiates => 'Variant Also Negotiates',
            self::InsufficientStorage => 'Insufficient Storage',
            self::LoopDetected => 'Loop Detected',
            self::NotExtended => 'Not Extended',
            self::NetworkAuthenticationRequired => 'Network Authentication Required',
            default => 'Unknown Status',
        };
    }
}
