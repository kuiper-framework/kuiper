<?php

declare(strict_types=1);

namespace kuiper\tars\config;

use DI\Annotation\Inject;
use kuiper\annotations\AnnotationReaderInterface;
use kuiper\di\annotation\Bean;
use kuiper\di\ComponentCollection;
use kuiper\di\ContainerBuilderAwareTrait;
use kuiper\di\DefinitionConfiguration;
use kuiper\rpc\RpcMethodFactoryInterface;
use kuiper\rpc\RpcRequestHandlerInterface;
use kuiper\rpc\server\middleware\AccessLog;
use kuiper\rpc\server\RpcServerRequestFactoryInterface;
use kuiper\rpc\server\RpcServerResponseFactoryInterface;
use kuiper\rpc\server\RpcServerRpcRequestHandler;
use kuiper\serializer\NormalizerInterface;
use kuiper\swoole\Application;
use kuiper\swoole\event\ReceiveEvent;
use kuiper\tars\annotation\TarsServant;
use kuiper\tars\server\ClientProperties;
use kuiper\tars\server\ServerProperties;
use kuiper\tars\server\TarsServerMethodFactory;
use kuiper\tars\server\TarsServerRequestFactory;
use kuiper\tars\server\TarsServerResponseFactory;
use kuiper\tars\server\TarsTcpReceiveEventListener;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;

class TarsServerConfiguration implements DefinitionConfiguration
{
    use ContainerBuilderAwareTrait;

    public function getDefinitions(): array
    {
        Application::getInstance()->getConfig()->mergeIfNotExists([
            'application' => [
                'logging' => [
                    'logger' => [
                        AccessLog::class => 'AccessLogLogger',
                    ],
                ],
                'tars' => [
                    'server' => [
                        'middleware' => [
                            AccessLog::class,
                        ],
                    ],
                ],
                'listeners' => [
                    ReceiveEvent::class => TarsTcpReceiveEventListener::class,
                ],
            ],
        ]);

        return [
        ];
    }

    /**
     * @Bean("tarsServices")
     */
    public function tarsServices(ContainerInterface $container, ServerProperties $serverProperties): array
    {
        $services = [];
        /** @var TarsServant $annotation */
        foreach (ComponentCollection::getAnnotations(TarsServant::class) as $annotation) {
            $serviceImpl = $container->get($annotation->getComponentId());
            $services[$serverProperties->getServerName().'.'.$annotation->value] = $serviceImpl;
        }

        return $services;
    }

    /**
     * @Bean("tarsServerMethodFactory")
     */
    public function tarsServerMethodFactory(ServerProperties $serverProperties, array $services, AnnotationReaderInterface $annotationReader): RpcMethodFactoryInterface
    {
        return new TarsServerMethodFactory($serverProperties, $services, $annotationReader);
    }

    /**
     * @Bean
     */
    public function tarsServerResponseFactory(
        ResponseFactoryInterface $httpResponseFactory,
        StreamFactoryInterface $streamFactory
    ): RpcServerResponseFactoryInterface {
        return new TarsServerResponseFactory($httpResponseFactory, $streamFactory);
    }

    /**
     * @Bean
     * @Inject({"rpcMethodFactory": "tarsServerMethodFactory"})
     */
    public function tarsServerRequestFactory(ServerProperties $serverProperties, RpcMethodFactoryInterface $rpcMethodFactory): RpcServerRequestFactoryInterface
    {
        return new TarsServerRequestFactory($serverProperties, $rpcMethodFactory);
    }

    /**
     * @Bean("tarsServerMiddlewares")
     */
    public function tarsServerMiddlewares(ContainerInterface $container): array
    {
        $middlewares = [];
        foreach (Application::getInstance()->getConfig()->get('application.tars.server.middleware', []) as $middleware) {
            $middlewares[] = $container->get($middleware);
        }

        return $middlewares;
    }

    /**
     * @Bean
     * @Inject({"services": "tarsServices", "middlewares": "tarsServerMiddlewares"})
     */
    public function tarsRequestHandler(RpcServerResponseFactoryInterface $responseFactory, array $services, array $middlewares): RpcRequestHandlerInterface
    {
        return new RpcServerRpcRequestHandler($services, $responseFactory, $middlewares);
    }

    /**
     * @Bean
     */
    public function serverProperties(NormalizerInterface $normalizer): ServerProperties
    {
        return $normalizer->denormalize(Application::getInstance()->getConfig()->get('application.tars.server'), ServerProperties::class);
    }

    /**
     * @Bean
     */
    public function clientProperties(NormalizerInterface $normalizer): ClientProperties
    {
        return $normalizer->denormalize(Application::getInstance()->getConfig()->get('application.tars.client'), ClientProperties::class);
    }
}
