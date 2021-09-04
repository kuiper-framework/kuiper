<?php

declare(strict_types=1);

namespace kuiper\jsonrpc\config;

use kuiper\di\annotation\Bean;
use kuiper\di\ComponentCollection;
use kuiper\di\ContainerBuilderAwareTrait;
use kuiper\di\DefinitionConfiguration;
use kuiper\jsonrpc\annotation\JsonRpcService;
use kuiper\jsonrpc\server\JsonRpcServerFactory;
use kuiper\swoole\Application;
use Psr\Container\ContainerInterface;

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
            $service = null;
            if (null !== $annotation->service) {
                $service = $annotation->service;
            } else {
                $class = $annotation->getTarget();
                foreach ($class->getInterfaceNames() as $interfaceName) {
                    $parts = explode('\\', $interfaceName);
                    $serviceName = end($parts);
                    if (false !== strpos($class->getShortName(), $serviceName)) {
                        $service = $interfaceName;
                    }
                }
                if (null === $service) {
                    throw new \InvalidArgumentException('Cannot resolve service name from '.$class->getName());
                }
            }
            $services[$service] = $container->get($annotation->getComponentId());
        }

        return $services;
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
     */
    public function jsonRpcServerFactory(ContainerInterface $container): JsonRpcServerFactory
    {
        return JsonRpcServerFactory::createFromContainer($container);
    }
}
