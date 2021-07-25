<?php

declare(strict_types=1);

namespace kuiper\tars\config;

use DI\Annotation\Inject;
use function DI\get;
use GuzzleHttp\Psr7\HttpFactory;
use kuiper\annotations\AnnotationReaderInterface;
use kuiper\di\annotation\Bean;
use kuiper\di\ComponentCollection;
use kuiper\di\ContainerBuilderAwareTrait;
use kuiper\di\DefinitionConfiguration;
use kuiper\logger\LoggerFactoryInterface;
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
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Log\LoggerInterface;

class TarsServerConfiguration implements DefinitionConfiguration
{
    use ContainerBuilderAwareTrait;

    protected const TAG = '['.__CLASS__.'] ';

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
            'guzzleHttpFactory' => get(HttpFactory::class),
        ];
    }

    /**
     * @Bean
     * @Inject({
     *     "httpRequestFactory": "guzzleHttpFactory",
     *     "serverRequestFactory": "tarsServerRequestFactory",
     *     "requestHandler": "tarsRequestHandler"
     *     })
     */
    public function tarsTcpReceiveEventListener(
        RequestFactoryInterface $httpRequestFactory,
        RpcServerRequestFactoryInterface $serverRequestFactory,
        RpcRequestHandlerInterface $requestHandler,
        LoggerFactoryInterface $loggerFactory): TarsTcpReceiveEventListener
    {
        $tarsTcpReceiveEventListener = new TarsTcpReceiveEventListener($httpRequestFactory, $serverRequestFactory, $requestHandler);
        $tarsTcpReceiveEventListener->setLogger($loggerFactory->create(TarsTcpReceiveEventListener::class));

        return $tarsTcpReceiveEventListener;
    }

    /**
     * @Bean("tarsServices")
     */
    public function tarsServices(ContainerInterface $container, ServerProperties $serverProperties): array
    {
        $logger = $container->get(LoggerInterface::class);
        $services = [];
        /** @var TarsServant $annotation */
        foreach (ComponentCollection::getAnnotations(TarsServant::class) as $annotation) {
            $serviceImpl = $container->get($annotation->getComponentId());
            $servantName = $serverProperties->getServerName().'.'.$annotation->value;
            $services[$servantName] = $serviceImpl;
            $logger->info(self::TAG."register servant $servantName");
        }

        return $services;
    }

    /**
     * @Bean("tarsServerMethodFactory")
     * @Inject({"services": "tarsServices"})
     */
    public function tarsServerMethodFactory(ServerProperties $serverProperties, array $services, AnnotationReaderInterface $annotationReader): RpcMethodFactoryInterface
    {
        return new TarsServerMethodFactory($serverProperties, $services, $annotationReader);
    }

    /**
     * @Bean("tarsServerResponseFactory")
     */
    public function tarsServerResponseFactory(ResponseFactoryInterface $httpResponseFactory, StreamFactoryInterface $streamFactory): RpcServerResponseFactoryInterface
    {
        return new TarsServerResponseFactory($httpResponseFactory, $streamFactory);
    }

    /**
     * @Bean("tarsServerRequestFactory")
     * @Inject({"rpcMethodFactory": "tarsServerMethodFactory"})
     */
    public function tarsServerRequestFactory(ServerProperties $serverProperties, RpcMethodFactoryInterface $rpcMethodFactory, LoggerFactoryInterface $loggerFactory): RpcServerRequestFactoryInterface
    {
        $tarsServerRequestFactory = new TarsServerRequestFactory($serverProperties, $rpcMethodFactory);
        $tarsServerRequestFactory->setLogger($loggerFactory->create(TarsServerRequestFactory::class));

        return $tarsServerRequestFactory;
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
     * @Bean("tarsRequestHandler")
     * @Inject({
     *     "services": "tarsServices",
     *     "middlewares": "tarsServerMiddlewares",
     *     "responseFactory": "tarsServerResponseFactory"
     * })
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
