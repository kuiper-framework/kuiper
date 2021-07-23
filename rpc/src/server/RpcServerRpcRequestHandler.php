<?php

declare(strict_types=1);

namespace kuiper\rpc\server;

use kuiper\rpc\exception\ServiceNotFoundException;
use kuiper\rpc\MiddlewareSupport;
use kuiper\rpc\RpcRequestHandlerInterface;
use kuiper\rpc\RpcRequestInterface;
use kuiper\rpc\RpcResponseInterface;

class RpcServerRpcRequestHandler implements RpcRequestHandlerInterface
{
    use MiddlewareSupport;

    /**
     * @var object[]
     */
    private $services;

    /**
     * @var RpcServerResponseFactoryInterface
     */
    private $responseFactory;

    public function __construct(array $services, RpcServerResponseFactoryInterface $responseFactory, array $middlewares = [])
    {
        $this->services = $services;
        $this->responseFactory = $responseFactory;
        $this->middlewares = $middlewares;
    }

    /**
     * {@inheritDoc}
     */
    public function handle(RpcRequestInterface $request): RpcResponseInterface
    {
        return $this->buildMiddlewareStack(new class($this) implements RpcRequestHandlerInterface {
            /**
             * @var RpcServerRpcRequestHandler
             */
            private $handler;

            public function __construct(RpcServerRpcRequestHandler $handler)
            {
                $this->handler = $handler;
            }

            public function handle(RpcRequestInterface $request): RpcResponseInterface
            {
                return $this->handler->serve($request);
            }
        })->handle($request);
    }

    public function serve(RpcRequestInterface $request): RpcResponseInterface
    {
        $method = $request->getInvokingMethod();
        $args = $method->getArguments();
        $target = $this->resolve($method->getTargetClass());
        $reflectionMethod = new \ReflectionMethod($target, $method->getMethodName());
        $parameters = [];
        $out = [];
        $outIndex = 0;
        foreach ($reflectionMethod->getParameters() as $i => $parameter) {
            if ($parameter->isPassedByReference()) {
                $out[$outIndex] = null;
                $parameters[] = &$out[$outIndex];
                ++$outIndex;
            } else {
                $parameters[] = $args[$i] ?? null;
            }
        }
        $return = call_user_func_array([$target, $method->getMethodName()], $parameters);
        $method->setResult(array_merge([$return], $out));

        return $this->responseFactory->createResponse($request);
    }

    private function resolve(string $targetClass): object
    {
        if (!isset($this->services[$targetClass])) {
            throw new ServiceNotFoundException("Service {$targetClass} not found");
        }

        return $this->services[$targetClass];
    }
}
