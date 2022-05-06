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

use kuiper\rpc\DelegateRequestHandler;
use kuiper\rpc\ErrorHandlerInterface;
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

    public function __construct(
        private readonly array $services,
        private readonly RpcServerResponseFactoryInterface $responseFactory,
        private readonly ErrorHandlerInterface $errorHandler,
        array $middlewares = [])
    {
        $this->middlewares = $middlewares;
    }

    /**
     * {@inheritDoc}
     */
    public function handle(RpcRequestInterface $request): RpcResponseInterface
    {
        return $this->buildMiddlewareStack(new DelegateRequestHandler([$this, 'serve']))
            ->handle($request);
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
            $target = $this->resolve($request, $method->getServiceLocator()->getName());
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
        try {
            $return = call_user_func_array([$target, $method->getMethodName()], $parameters);
        } catch (\Exception $e) {
            return $this->errorHandler->handle($request, $e);
        }
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
