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

namespace kuiper\rpc\server;

use kuiper\rpc\RpcRequestInterface;
use kuiper\swoole\coroutine\Coroutine;

class ServerRequestHolder
{
    private const REQUEST_CONTEXT_KEY = '__RpcServerRequest';

    public static function setRequest(RpcRequestInterface $request): void
    {
        Coroutine::getContext()[self::REQUEST_CONTEXT_KEY] = $request;
    }

    public static function getRequest(): ?RpcRequestInterface
    {
        return Coroutine::getContext()[self::REQUEST_CONTEXT_KEY] ?? null;
    }
}
