<?php

declare(strict_types=1);

namespace kuiper\jsonrpc\config;

use DI\Annotation\Inject;
use kuiper\di\annotation\Bean;
use kuiper\di\ComponentCollection;
use kuiper\di\ContainerBuilderAwareTrait;
use kuiper\di\DefinitionConfiguration;
use kuiper\jsonrpc\annotation\JsonRpcService;
use kuiper\jsonrpc\server\JsonRpcServerRequestFactory;
use kuiper\jsonrpc\server\JsonRpcServerResponse;
use kuiper\jsonrpc\server\JsonRpcServerResponseFactory;
use kuiper\jsonrpc\server\OutParamJsonRpcServerResponse;
use kuiper\reflection\ReflectionDocBlockFactoryInterface;
use kuiper\rpc\RpcRequestHandlerInterface;
use kuiper\rpc\server\RpcServerRequestFactoryInterface;
use kuiper\rpc\server\RpcServerResponseFactoryInterface;
use kuiper\rpc\server\RpcServerRpcRequestHandler;
use kuiper\serializer\NormalizerInterface;
use kuiper\swoole\Application;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;

abstract class AbstractJsonRpcServerConfiguration implements DefinitionConfiguration
{
    use ContainerBuilderAwareTrait;

    /**
     * @Bean("jsonrpcServices")
     */
    public function jsonrpcServices(ContainerInterface $container): array
    {
        $services = [];
        /** @var JsonRpcService $annotation */
        foreach (ComponentCollection::getAnnotations(JsonRpcService::class) as $annotation) {
            $serviceImpl = $container->get($annotation->getComponentId());
            $service = null;
            if (null !== $annotation->service) {
                $service = $annotation->service;
            } else {
                $class = new \ReflectionClass($serviceImpl);
                foreach ($class->getInterfaceNames() as $interfaceName) {
                    $parts = explode('\\', $interfaceName);
                    $serviceName = end($parts);
                    if (false !== strpos($class->getShortName(), $serviceName)) {
                        $service = $interfaceName;
                    }
                }
            }
            $services[$service] = $serviceImpl;
        }

        return $services;
    }

    /**
     * @Bean
     */
    public function jsonrpcServerResponseFactory(ResponseFactoryInterface $httpResponseFactory, StreamFactoryInterface $streamFactory): RpcServerResponseFactoryInterface
    {
        $responseClass = Application::getInstance()->getConfig()->getBool('application.jsonrpc.server.enable_out_param')
            ? OutParamJsonRpcServerResponse::class
            : JsonRpcServerResponse::class;

        return new JsonRpcServerResponseFactory($httpResponseFactory, $streamFactory, $responseClass);
    }

    /**
     * @Bean
     * @Inject({"services": "jsonrpcServices"})
     */
    public function jsonrpcServerRequestFactory(array $services, NormalizerInterface $normalizer, ReflectionDocBlockFactoryInterface $reflectionDocBlockFactory): RpcServerRequestFactoryInterface
    {
        return new JsonRpcServerRequestFactory($services, $normalizer, $reflectionDocBlockFactory);
    }

    /**
     * @Bean("jsonrpcServerMiddlewares")
     */
    public function jsonrpcServerMiddlewares(ContainerInterface $container): array
    {
        $middlewares = [];
        foreach (Application::getInstance()->getConfig()->get('application.jsonrpc.server.middleware', []) as $middleware) {
            $middlewares[] = $container->get($middleware);
        }

        return $middlewares;
    }

    /**
     * @Bean
     * @Inject({"services": "jsonrpcServices", "middlewares": "jsonrpcServerMiddlewares"})
     */
    public function jsonrpcRequestHandler(ContainerInterface $container, RpcServerResponseFactoryInterface $responseFactory, array $services, array $middlewares): RpcRequestHandlerInterface
    {
        return new RpcServerRpcRequestHandler($services, $responseFactory, $middlewares);
    }
}
