<?php

declare(strict_types=1);

namespace kuiper\swoole\http;

use kuiper\swoole\coroutine\Coroutine;
use Psr\Http\Message\ServerRequestInterface;

class ServerRequestHolder
{
    private const REQUEST_CONTEXT_KEY = '__HttpServerRequest';

    public static function setRequest(ServerRequestInterface $request): void
    {
        Coroutine::getContext()[self::REQUEST_CONTEXT_KEY] = $request;
    }

    public static function getRequest(): ?ServerRequestInterface
    {
        return Coroutine::getContext()[self::REQUEST_CONTEXT_KEY] ?? null;
    }
}
