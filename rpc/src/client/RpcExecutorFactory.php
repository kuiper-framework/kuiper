<?php

declare(strict_types=1);

namespace kuiper\rpc\client;

use kuiper\annotations\AnnotationReaderInterface;
use kuiper\di\ContainerAwareInterface;
use kuiper\di\ContainerAwareTrait;
use kuiper\helper\Arrays;
use kuiper\rpc\MiddlewareFactoryInterface;
use kuiper\rpc\RpcRequestHandlerInterface;

class RpcExecutorFactory implements RpcExecutorFactoryInterface, ContainerAwareInterface
{
    use ContainerAwareTrait;

    /**
     * @var RpcRequestFactoryInterface
     */
    private $requestFactory;

    /**
     * @var RpcRequestHandlerInterface
     */
    private $rpcRequestHandler;

    /**
     * @var array
     */
    private $middlewares;

    /**
     * @var array
     */
    private $methodMiddlewares;

    public function __construct(RpcRequestFactoryInterface $requestFactory, RpcRequestHandlerInterface $requestHandler, array $middlewares = [])
    {
        $this->rpcRequestHandler = $requestHandler;
        $this->requestFactory = $requestFactory;
        $this->middlewares = $middlewares;
    }

    /**
     * {@inheritDoc}
     */
    public function createExecutor(object $proxy, string $method, array $args): RpcExecutorInterface
    {
        $middlewares = $this->middlewares;
        if (null !== $this->container && $this->container->has(AnnotationReaderInterface::class)) {
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
        $annotationReader = $this->container->get(AnnotationReaderInterface::class);
        $reflectionMethod = new \ReflectionMethod($proxy, $method);
        $reflectionClass = $reflectionMethod->getDeclaringClass();
        $annotations = [];
        foreach ($reflectionClass->getInterfaces() as $interface) {
            if ($interface->hasMethod($method)) {
                $annotations[] = $this->getMethodAnnotations($annotationReader, $interface->getMethod($method));
            }
        }
        if (false !== $reflectionClass->getParentClass()
            && $reflectionClass->getParentClass()->hasMethod($method)) {
            $annotations[] = $this->getMethodAnnotations($annotationReader, $reflectionClass->getParentClass()->getMethod($method));
        }
        $annotations[] = $this->getMethodAnnotations($annotationReader, $reflectionMethod);
        $middlewares = [];
        /** @var MiddlewareFactoryInterface $annotation */
        foreach (array_merge(...$annotations) as $annotation) {
            $middlewares[$annotation->getPriority()][] = $annotation->create($this->container);
        }
        ksort($middlewares);

        return $this->methodMiddlewares[$key] = Arrays::flatten($middlewares);
    }

    private function getMethodAnnotations(AnnotationReaderInterface $annotationReader, \ReflectionMethod $method): array
    {
        $annotations = [];
        foreach ($annotationReader->getMethodAnnotations($method) as $annotation) {
            if ($annotation instanceof MiddlewareFactoryInterface) {
                $annotations[get_class($annotation)] = $annotation;
            }
        }
        foreach ($annotationReader->getClassAnnotations($method->getDeclaringClass()) as $annotation) {
            if ($annotation instanceof MiddlewareFactoryInterface
                && !isset($annotations[get_class($annotation)])) {
                $annotations[get_class($annotation)] = $annotation;
            }
        }

        return $annotations;
    }
}
