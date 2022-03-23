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

namespace kuiper\rpc\client;

use kuiper\rpc\RpcRequestInterface;
use kuiper\swoole\coroutine\Coroutine;

class RpcRequestHolder
{
    private const REQUEST_CONTEXT_KEY = '__RpcRequest';

    public static function push(RpcRequestInterface $request): void
    {
        Coroutine::getContext()[self::REQUEST_CONTEXT_KEY][] = $request;
    }

    public static function pop(): void
    {
        $context = Coroutine::getContext();
        if (isset($context[self::REQUEST_CONTEXT_KEY])) {
            array_pop($context[self::REQUEST_CONTEXT_KEY]);
        }
    }

    public static function peek(): ?RpcRequestInterface
    {
        $context = Coroutine::getContext();
        if (!empty($context[self::REQUEST_CONTEXT_KEY])) {
            return end($context[self::REQUEST_CONTEXT_KEY]);
        }

        return null;
    }
}
