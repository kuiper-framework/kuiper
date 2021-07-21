<?php

declare(strict_types=1);

namespace kuiper\rpc\server;

use kuiper\rpc\exception\ServiceNotFoundException;
use kuiper\rpc\MiddlewareSupport;
use kuiper\rpc\RequestHandlerInterface;
use kuiper\rpc\RequestInterface;
use kuiper\rpc\ResponseInterface;

class RpcServerRequestHandler implements RequestHandlerInterface
{
    use MiddlewareSupport;

    /**
     * @var object[]
     */
    private $services;

    /**
     * @var ServerResponseFactoryInterface
     */
    private $responseFactory;

    public function __construct(array $services, ServerResponseFactoryInterface $responseFactory, array $middlewares = [])
    {
        $this->services = $services;
        $this->responseFactory = $responseFactory;
        $this->middlewares = $middlewares;
    }

    /**
     * {@inheritDoc}
     */
    public function handle(RequestInterface $request): ResponseInterface
    {
        return $this->buildMiddlewareStack(new class($this) implements RequestHandlerInterface {
            /**
             * @var RpcServerRequestHandler
             */
            private $handler;

            public function __construct(RpcServerRequestHandler $handler)
            {
                $this->handler = $handler;
            }

            public function handle(RequestInterface $request): ResponseInterface
            {
                return $this->handler->serve($request);
            }
        })->handle($request);
    }

    public function serve(RequestInterface $request): ResponseInterface
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
