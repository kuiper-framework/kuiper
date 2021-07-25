<?php

declare(strict_types=1);

namespace kuiper\jsonrpc\config;

use DI\Annotation\Inject;
use function DI\autowire;
use DI\Container;
use function DI\factory;
use function DI\get;
use GuzzleHttp\Psr7\HttpFactory;
use kuiper\di\annotation\Bean;
use kuiper\di\ComponentCollection;
use kuiper\di\ContainerBuilderAwareTrait;
use kuiper\di\DefinitionConfiguration;
use kuiper\helper\Arrays;
use kuiper\helper\Text;
use kuiper\http\client\HttpClientFactoryInterface;
use kuiper\jsonrpc\annotation\JsonRpcClient;
use kuiper\jsonrpc\client\JsonRpcMethodFactory;
use kuiper\jsonrpc\client\JsonRpcRequestFactory;
use kuiper\jsonrpc\client\JsonRpcResponseFactory;
use kuiper\jsonrpc\client\NoOutParamJsonRpcResponseFactory;
use kuiper\jsonrpc\JsonRpcProtocol;
use kuiper\logger\LoggerFactoryInterface;
use kuiper\rpc\client\ProxyGenerator;
use kuiper\rpc\client\ProxyGeneratorInterface;
use kuiper\rpc\client\RpcRequestFactoryInterface;
use kuiper\rpc\client\RpcResponseFactoryInterface;
use kuiper\rpc\client\RpcResponseNormalizer;
use kuiper\rpc\RpcMethodFactoryInterface;
use kuiper\rpc\server\middleware\AccessLog;
use kuiper\rpc\transporter\Endpoint;
use kuiper\rpc\transporter\HttpTransporter;
use kuiper\rpc\transporter\PooledTransporter;
use kuiper\rpc\transporter\SwooleCoroutineTcpTransporter;
use kuiper\rpc\transporter\SwooleTcpTransporter;
use kuiper\rpc\transporter\TransporterInterface;
use kuiper\serializer\normalizer\ExceptionNormalizer;
use kuiper\swoole\Application;
use kuiper\swoole\config\ServerConfiguration;
use kuiper\swoole\constants\ClientSettings;
use kuiper\swoole\constants\ServerType;
use kuiper\swoole\coroutine\Coroutine;
use kuiper\swoole\pool\PoolFactoryInterface;
use kuiper\web\LineRequestLogFormatter;
use kuiper\web\RequestLogFormatterInterface;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;

class JsonRpcClientConfiguration implements DefinitionConfiguration
{
    use ContainerBuilderAwareTrait;

    public function getDefinitions(): array
    {
        $this->addJsonRpcRequestLog();
        $this->containerBuilder->defer(function (ContainerInterface $container): void {
            $this->createJsonRpcClients($container);
        });

        return [
            ProxyGeneratorInterface::class => autowire(ProxyGenerator::class),
            RequestLogFormatterInterface::class => autowire(LineRequestLogFormatter::class),
            'guzzleHttpRequestFactory' => get(HttpFactory::class),
        ];
    }

    /**
     * @Bean("jsonrpcRequestLog")
     */
    public function jsonrpcRequestLog(RequestLogFormatterInterface $requestLogFormatter, LoggerFactoryInterface $loggerFactory): AccessLog
    {
        $middleware = new AccessLog($requestLogFormatter);
        $middleware->setLogger($loggerFactory->create('JsonRpcRequestLogger'));

        return $middleware;
    }

    private function createJsonRpcClients(ContainerInterface $container): void
    {
        /** @var array $services */
        $services = $container->has('jsonrpcServices');
        /** @var JsonRpcClient $annotation */
        foreach (ComponentCollection::getAnnotations(JsonRpcClient::class) as $annotation) {
            $clientInterfaceName = $annotation->getTargetClass();
            $service = $annotation->service ?? $clientInterfaceName;
            if (isset($services[$service])) {
                continue;
            }
            /** @var Container $container */
            $container->set($clientInterfaceName, factory(function () use ($container, $annotation) {
                $clientInterfaceName = $annotation->getTargetClass();
                $proxyClass = $container->get(ProxyGeneratorInterface::class)->generate($clientInterfaceName);
                $proxyClass->eval();
                $class = $proxyClass->getClassName();

                $options = array_merge(
                    Arrays::mapKeys(get_object_vars($annotation), [Text::class, 'snakeCase']),
                    Application::getInstance()->getConfig()
                        ->get('application.jsonrpc.client.options', [])[$clientInterfaceName] ?? []
                );
                if (isset($options['protocol'])) {
                    $protocol = $options['protocol'];
                } elseif (isset($options['base_uri'])) {
                    $protocol = ServerType::HTTP;
                } elseif (isset($options['endpoint'])) {
                    $endpoint = Endpoint::fromString($options['endpoint']);
                    $protocol = $endpoint->getProtocol();
                } else {
                    $protocol = ServerType::HTTP;
                }
                if (ServerType::TCP === $protocol) {
                    $client = $container->get('jsonrpcTcpClientFactory')($clientInterfaceName, $options);
                } else {
                    $client = $container->get('jsonrpcHttpClientFactory')($clientInterfaceName, $options);
                }

                return new $class($client);
            }));
        }
    }

    /**
     * @Bean("jsonrpcTcpClientFactory")
     * @Inject({
     *     "requestFactory": "jsonrpcRequestFactory",
     *     "middlewares": "jsonrpcClientMiddlewares"
     *     })
     */
    public function jsonrpcTcpClientFactory(
        LoggerFactoryInterface $loggerFactory,
        PoolFactoryInterface $poolFactory,
        ResponseFactoryInterface $httpResponseFactory,
        RpcRequestFactoryInterface $requestFactory,
        RpcResponseNormalizer $responseNormalizer,
        ExceptionNormalizer $exceptionNormalizer,
        array $middlewares): callable
    {
        return function (string $name, array $options) use (
            $loggerFactory, $poolFactory, $requestFactory, $httpResponseFactory, $responseNormalizer, $exceptionNormalizer, $middlewares
        ): \kuiper\jsonrpc\client\JsonRpcClient {
            $responseFactory = $this->createJsonRpcResponseFactory($responseNormalizer, $exceptionNormalizer, $options['out_params'] ?? false);
            $logger = $loggerFactory->create($name);
            $transporter = new PooledTransporter($poolFactory->create($name, function ($connId) use ($logger, $name, $options, $httpResponseFactory): TransporterInterface {
                $options = array_merge([
                    ClientSettings::OPEN_LENGTH_CHECK => false,
                    ClientSettings::OPEN_EOF_CHECK => true,
                    ClientSettings::PACKAGE_EOF => JsonRpcProtocol::EOF,
                ], $options);
                $connectionClass = Coroutine::isEnabled() ? SwooleCoroutineTcpTransporter::class : SwooleTcpTransporter::class;
                $logger->info("[$name] create connection $connId", ['class' => $connectionClass]);
                $transporter = new $connectionClass($httpResponseFactory, $options);
                $transporter->setLogger($logger);

                return $transporter;
            }));

            return new \kuiper\jsonrpc\client\JsonRpcClient($transporter, $requestFactory, $responseFactory, $middlewares);
        };
    }

    /**
     * @Bean("jsonrpcHttpClientFactory")
     * @Inject({
     *     "requestFactory": "jsonrpcRequestFactory",
     *     "middlewares": "jsonrpcClientMiddlewares"
     *     })
     */
    public function jsonrpcHttpClientFactory(
        HttpClientFactoryInterface $httpClientFactory,
        RpcRequestFactoryInterface $requestFactory,
        RpcResponseNormalizer $responseNormalizer,
        ExceptionNormalizer $exceptionNormalizer,
        array $middlewares): callable
    {
        return function (string $name, array $options) use ($httpClientFactory, $requestFactory, $responseNormalizer, $exceptionNormalizer, $middlewares): \kuiper\jsonrpc\client\JsonRpcClient {
            $responseFactory = $this->createJsonRpcResponseFactory($responseNormalizer, $exceptionNormalizer, $options['out_params'] ?? false);
            $transporter = new HttpTransporter($httpClientFactory->create($options));

            return new \kuiper\jsonrpc\client\JsonRpcClient($transporter, $requestFactory, $responseFactory, $middlewares);
        };
    }

    /**
     * @Bean("jsonrpcMethodFactory")
     */
    public function jsonrpcMethodFactory(): RpcMethodFactoryInterface
    {
        return new JsonRpcMethodFactory();
    }

    /**
     * @Bean("jsonrpcRequestFactory")
     * @Inject({
     *     "httpRequestFactory": "guzzleHttpRequestFactory",
     *     "rpcMethodFactory": "jsonrpcMethodFactory"
     * })
     */
    public function jsonrpcRequestFactory(RequestFactoryInterface $httpRequestFactory, StreamFactoryInterface $streamFactory, RpcMethodFactoryInterface $rpcMethodFactory): RpcRequestFactoryInterface
    {
        return new JsonRpcRequestFactory($httpRequestFactory, $streamFactory, $rpcMethodFactory);
    }

    /**
     * @Bean("jsonrpcClientMiddlewares")
     */
    public function jsonrpcClientMiddlewares(ContainerInterface $container): array
    {
        $middlewares = [];
        foreach (Application::getInstance()->getConfig()->get('application.jsonrpc.client.middleware', []) as $middleware) {
            $middlewares[] = $container->get($middleware);
        }

        return $middlewares;
    }

    private function addJsonRpcRequestLog(): void
    {
        $serverConfiguration = new ServerConfiguration();
        $config = Application::getInstance()->getConfig();
        $path = $config->get('application.logging.path');
        if (null === $path) {
            return;
        }
        $config->mergeIfNotExists([
            'application' => [
                'logging' => [
                    'loggers' => [
                        'JsonRpcRequestLogger' => $serverConfiguration->createAccessLogger($path.'/jsonrpc-client.log'),
                    ],
                    'logger' => [
                        'JsonRpcRequestLogger' => 'JsonRpcRequestLogger',
                    ],
                ],
                'jsonrpc' => [
                    'client' => [
                        'middleware' => [
                            'jsonrpcRequestLog',
                        ],
                    ],
                ],
            ],
        ]);
    }

    /**
     * @param bool                  $outParams
     * @param RpcResponseNormalizer $responseNormalizer
     * @param ExceptionNormalizer   $exceptionNormalizer
     *
     * @return JsonRpcResponseFactory|NoOutParamJsonRpcResponseFactory
     */
    protected function createJsonRpcResponseFactory(RpcResponseNormalizer $responseNormalizer, ExceptionNormalizer $exceptionNormalizer, bool $outParams): RpcResponseFactoryInterface
    {
        if ($outParams) {
            return new JsonRpcResponseFactory($responseNormalizer, $exceptionNormalizer);
        }

        return new NoOutParamJsonRpcResponseFactory($responseNormalizer, $exceptionNormalizer);
    }
}
