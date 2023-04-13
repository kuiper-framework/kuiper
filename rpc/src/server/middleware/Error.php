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

namespace kuiper\rpc\server\middleware;

use Exception;
use kuiper\rpc\ErrorHandlerInterface;
use kuiper\rpc\MiddlewareInterface;
use kuiper\rpc\RpcRequestHandlerInterface;
use kuiper\rpc\RpcRequestInterface;
use kuiper\rpc\RpcResponseInterface;
use Throwable;

class Error implements MiddlewareInterface
{
    public function __construct(private readonly ErrorHandlerInterface $errorHandler)
    {
    }

    public function process(RpcRequestInterface $request, RpcRequestHandlerInterface $handler): RpcResponseInterface
    {
        try {
            return $handler->handle($request);
        } catch (Throwable $e) {
            return $this->handleException($request, $e);
        }
    }

    private function handleException(RpcRequestInterface $request, Throwable|Exception $e): RpcResponseInterface
    {
        return $this->errorHandler->handle($request, $e);
    }
}
