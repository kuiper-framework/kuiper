<?php

/*
 * This file is part of the Kuiper package.
 *
 * (c) Ye Wenbin <wenbinye@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

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
