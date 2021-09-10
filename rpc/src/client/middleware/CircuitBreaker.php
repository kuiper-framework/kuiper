<?php

declare(strict_types=1);

namespace kuiper\rpc\client\middleware;

use kuiper\resilience\circuitbreaker\CircuitBreakerFactory;
use kuiper\rpc\MiddlewareInterface;
use kuiper\rpc\RpcRequestHandlerInterface;
use kuiper\rpc\RpcRequestInterface;
use kuiper\rpc\RpcResponseInterface;

class CircuitBreaker implements MiddlewareInterface
{
    /**
     * @var CircuitBreakerFactory
     */
    private $circuitBreakerFactory;

    /**
     * CircuitBreaker constructor.
     *
     * @param CircuitBreakerFactory $circuitBreakerFactory
     */
    public function __construct(CircuitBreakerFactory $circuitBreakerFactory)
    {
        $this->circuitBreakerFactory = $circuitBreakerFactory;
    }

    public function process(RpcRequestInterface $request, RpcRequestHandlerInterface $handler): RpcResponseInterface
    {
        return $this->circuitBreakerFactory->create($request->getRpcMethod()->getServiceLocator()->getName())
            ->call([$handler, 'handle'], $request);
    }
}
