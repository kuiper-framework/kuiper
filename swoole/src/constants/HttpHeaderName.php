<?php

declare(strict_types=1);

namespace kuiper\swoole\constants;

class HttpHeaderName
{
    public const HOST = 'host';
    public const CONNECTION = 'connection';
    public const CONTENT_ENCODING = 'content-encoding';
    public const ACCEPT_ENCODING = 'accept-encoding';
    public const KEEPALIVE = 'keepalive';
    public const COOKIE = 'cookie';
    public const CONTENT_DISPOSITION = 'content-disposition';
    public const CONTENT_TYPE = 'content-type';
    public const X_FORWARDED_HOST = 'x-forwarded-host';
    public const X_FORWARDED_PROTO = 'x-forwarded-proto';
    public const DATE = 'date';
    public const SERVER = 'server';
    public const CONTENT_LENGTH = 'content-length';

    public static function getDisplayName(string $name): string
    {
        return implode('-', array_map('ucfirst', explode('-', $name)));
    }
}
