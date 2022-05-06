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

namespace kuiper\rpc\client\middleware;

use kuiper\resilience\circuitbreaker\CircuitBreakerFactory;
use kuiper\rpc\MiddlewareInterface;
use kuiper\rpc\RpcRequestHandlerInterface;
use kuiper\rpc\RpcRequestInterface;
use kuiper\rpc\RpcResponseInterface;

class CircuitBreaker implements MiddlewareInterface
{
    public function __construct(private readonly CircuitBreakerFactory $circuitBreakerFactory)
    {
    }

    public function process(RpcRequestInterface $request, RpcRequestHandlerInterface $handler): RpcResponseInterface
    {
        return $this->circuitBreakerFactory->create($request->getRpcMethod()->getServiceLocator()->getName())
            ->call([$handler, 'handle'], $request);
    }
}
