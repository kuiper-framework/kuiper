<?php

declare(strict_types=1);

namespace kuiper\rpc\server;

use kuiper\rpc\exception\ErrorCode;
use kuiper\rpc\exception\InvalidRequestException;
use kuiper\rpc\MiddlewareSupport;
use kuiper\rpc\RpcRequestHandlerInterface;
use kuiper\rpc\RpcRequestInterface;
use kuiper\rpc\RpcResponseInterface;
use ReflectionException;

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

    /**
     * @throws ReflectionException
     * @throws InvalidRequestException
     */
    public function serve(RpcRequestInterface $request): RpcResponseInterface
    {
        $method = $request->getRpcMethod();
        $args = $method->getArguments();
        $target = $method->getTarget();
        if (!is_object($target)) {
            $target = $this->resolve($request, $method->getServiceName());
        }
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
        $request = $request->withRpcMethod($method->withResult(array_merge([$return], $out)));

        return $this->responseFactory->createResponse($request);
    }

    /**
     * @throws InvalidRequestException
     */
    private function resolve(RpcRequestInterface $request, string $serviceName): object
    {
        if (!isset($this->services[$serviceName])) {
            throw new InvalidRequestException($request, "Service {$serviceName} not found", ErrorCode::INVALID_ARGUMENT);
        }

        return $this->services[$serviceName];
    }
}
