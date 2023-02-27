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

use kuiper\di\ContainerAwareInterface;
use kuiper\di\ContainerAwareTrait;
use kuiper\helper\Arrays;
use kuiper\rpc\MiddlewareFactoryInterface;
use kuiper\rpc\RpcRequestHandlerInterface;
use ReflectionAttribute;
use ReflectionMethod;

class RpcExecutorFactory implements RpcExecutorFactoryInterface, ContainerAwareInterface
{
    use ContainerAwareTrait;

    /**
     * @var array
     */
    private array $methodMiddlewares;

    public function __construct(
        private readonly RpcRequestFactoryInterface $requestFactory,
        private readonly RpcRequestHandlerInterface $rpcRequestHandler,
        private readonly array $middlewares = [])
    {
    }

    /**
     * {@inheritDoc}
     */
    public function createExecutor(object $proxy, string $method, array $args): RpcExecutorInterface
    {
        $middlewares = $this->middlewares;
        if (null !== $this->container) {
            $middlewares = array_merge($middlewares, $this->getMethodAnnotationMiddlewares($proxy, $method));
        }

        return new RpcExecutor($this->rpcRequestHandler, $this->requestFactory->createRequest($proxy, $method, $args), $middlewares);
    }

    private function getMethodAnnotationMiddlewares(object $proxy, string $method): array
    {
        $key = get_class($proxy).'::'.$method;
        if (isset($this->methodMiddlewares[$key])) {
            return $this->methodMiddlewares[$key];
        }
        $reflectionMethod = new ReflectionMethod($proxy, $method);
        $reflectionClass = $reflectionMethod->getDeclaringClass();
        $attributes = [];
        foreach ($reflectionClass->getInterfaces() as $interface) {
            if ($interface->hasMethod($method)) {
                $attributes[] = $this->getMethodAttributes($interface->getMethod($method));
            }
        }
        if (false !== $reflectionClass->getParentClass()
            && $reflectionClass->getParentClass()->hasMethod($method)) {
            $attributes[] = $this->getMethodAttributes($reflectionClass->getParentClass()->getMethod($method));
        }
        $attributes[] = $this->getMethodAttributes($reflectionMethod);
        $middlewares = [];
        /** @var MiddlewareFactoryInterface $attribute */
        foreach (array_merge(...$attributes) as $attribute) {
            $middlewares[$attribute->getPriority()][] = $attribute->create($this->container);
        }
        ksort($middlewares);

        return $this->methodMiddlewares[$key] = Arrays::flatten($middlewares);
    }

    private function getMethodAttributes(ReflectionMethod $method): array
    {
        $attributes = [];

        foreach ($method->getAttributes(MiddlewareFactoryInterface::class, ReflectionAttribute::IS_INSTANCEOF) as $attribute) {
            $middleware = $attribute->newInstance();
            $attributes[get_class($middleware)] = $middleware;
        }
        foreach ($method->getDeclaringClass()->getAttributes(MiddlewareFactoryInterface::class, ReflectionAttribute::IS_INSTANCEOF) as $attribute) {
            $middleware = $attribute->newInstance();
            if (!isset($attributes[get_class($middleware)])) {
                $attributes[get_class($middleware)] = $middleware;
            }
        }

        return $attributes;
    }
}
