<?php

declare(strict_types=1);

namespace kuiper\tars\config;

use DI\Annotation\Inject;
use function DI\autowire;
use DI\Container;
use function DI\factory;
use GuzzleHttp\Psr7\HttpFactory;
use kuiper\di\annotation\Bean;
use kuiper\di\ComponentCollection;
use kuiper\di\ContainerBuilderAwareTrait;
use kuiper\di\DefinitionConfiguration;
use kuiper\helper\Arrays;
use kuiper\helper\Text;
use kuiper\logger\LoggerFactoryInterface;
use kuiper\reflection\ReflectionDocBlockFactoryInterface;
use kuiper\rpc\client\ProxyGeneratorInterface;
use kuiper\rpc\client\RpcRequestFactoryInterface;
use kuiper\rpc\RpcMethodFactoryInterface;
use kuiper\rpc\server\middleware\AccessLog;
use kuiper\rpc\transporter\PooledTransporter;
use kuiper\rpc\transporter\SwooleCoroutineTcpTransporter;
use kuiper\rpc\transporter\SwooleTcpTransporter;
use kuiper\rpc\transporter\TransporterInterface;
use kuiper\swoole\Application;
use kuiper\swoole\config\ServerConfiguration;
use kuiper\swoole\coroutine\Coroutine;
use kuiper\swoole\pool\PoolFactoryInterface;
use kuiper\tars\annotation\TarsClient;
use kuiper\tars\client\TarsProxyGenerator;
use kuiper\tars\client\TarsRequestFactory;
use kuiper\tars\client\TarsResponseFactory;
use kuiper\tars\core\TarsMethodFactory;
use kuiper\web\LineRequestLogFormatter;
use kuiper\web\RequestLogFormatterInterface;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;

class TarsClientConfiguration implements DefinitionConfiguration
{
    use ContainerBuilderAwareTrait;

    public function getDefinitions(): array
    {
        $this->addTarsRequestLog();
        $this->containerBuilder->defer(function (ContainerInterface $container): void {
            $this->createTarsClients($container);
        });

        return [
            RequestLogFormatterInterface::class => autowire(LineRequestLogFormatter::class),
            'tarsMethodFactory' => autowire(TarsMethodFactory::class),
        ];
    }

    /**
     * @Bean("tarsProxyGenerator")
     */
    public function tarsProxyGenerator(ReflectionDocBlockFactoryInterface $reflectionDocBlockFactory): ProxyGeneratorInterface
    {
        return new TarsProxyGenerator($reflectionDocBlockFactory);
    }

    /**
     * @Bean("tarsRequestLog")
     */
    public function tarsRequestLog(RequestLogFormatterInterface $requestLogFormatter, LoggerFactoryInterface $loggerFactory): AccessLog
    {
        $middleware = new AccessLog($requestLogFormatter);
        $middleware->setLogger($loggerFactory->create('TarsRequestLogger'));

        return $middleware;
    }

    private function createTarsClients(ContainerInterface $container): void
    {
        /** @var TarsClient $annotation */
        foreach (ComponentCollection::getAnnotations(TarsClient::class) as $annotation) {
            /** @var Container $container */
            $container->set($annotation->getTargetClass(), factory(function () use ($container, $annotation) {
                $clientInterfaceName = $annotation->getTargetClass();
                $proxyClass = $container->get('tarsProxyGenerator')->generate($clientInterfaceName);
                $proxyClass->eval();
                $class = $proxyClass->getClassName();

                $options = array_merge(
                    Arrays::mapKeys(get_object_vars($annotation), [Text::class, 'snakeCase']),
                    Application::getInstance()->getConfig()
                        ->get('application.tars.client.options', [])[$clientInterfaceName] ?? []
                );
                $client = $container->get('tarsClientFactory')($clientInterfaceName, $options);

                return new $class($client);
            }));
        }
    }

    /**
     * @param string $clientInterfaceClass
     *
     * @return object
     *
     * @throws \ReflectionException
     */
    public function createClient(string $clientInterfaceClass, array $options = [])
    {
        $proxyGenerator = new TarsProxyGenerator();
        $proxyClass = $proxyGenerator->generate($clientInterfaceClass);
        $proxyClass->eval();
        $class = $proxyClass->getClassName();
        $httpFactory = new HttpFactory();
        $transporter = new SwooleTcpTransporter($httpFactory, $options);
        $tarsMethodFactory = new TarsMethodFactory();
        $responseFactory = new TarsResponseFactory();
        $requestFactory = new TarsRequestFactory($httpFactory, $httpFactory, $tarsMethodFactory);
        $tarsClient = new \kuiper\tars\client\TarsClient($transporter, $requestFactory, $responseFactory);

        return new $class($tarsClient);
    }

    /**
     * @Bean("tarsClientFactory")
     * @Inject({
     *     "requestFactory": "tarsRequestFactory",
     *     "middlewares": "tarsClientMiddlewares"
     *     })
     */
    public function tarsClientFactory(
        LoggerFactoryInterface $loggerFactory,
        PoolFactoryInterface $poolFactory,
        ResponseFactoryInterface $httpResponseFactory,
        RpcRequestFactoryInterface $requestFactory,
        array $middlewares): callable
    {
        return static function (string $name, array $options) use ($loggerFactory, $poolFactory, $requestFactory, $httpResponseFactory, $middlewares): \kuiper\tars\client\TarsClient {
            $logger = $loggerFactory->create($name);
            $transporter = new PooledTransporter($poolFactory->create($name, function ($connId) use ($logger, $name, $options, $httpResponseFactory): TransporterInterface {
                $connectionClass = Coroutine::isEnabled() ? SwooleCoroutineTcpTransporter::class : SwooleTcpTransporter::class;
                $logger->info("[$name] create connection $connId", ['class' => $connectionClass]);
                $transporter = new $connectionClass($httpResponseFactory, $options);
                $transporter->setLogger($logger);

                return $transporter;
            }));
            $responseFactory = new TarsResponseFactory();

            return new \kuiper\tars\client\TarsClient($transporter, $requestFactory, $responseFactory, $middlewares);
        };
    }

    /**
     * @Bean("tarsRequestFactory")
     * @Inject({"rpcMethodFactory": "tarsMethodFactory"})
     */
    public function tarsRequestFactory(
        RequestFactoryInterface $httpRequestFactory,
        StreamFactoryInterface $streamFactory,
        RpcMethodFactoryInterface $rpcMethodFactory): RpcRequestFactoryInterface
    {
        return new TarsRequestFactory($httpRequestFactory, $streamFactory, $rpcMethodFactory);
    }

    /**
     * @Bean("tarsClientMiddlewares")
     */
    public function tarsClientMiddlewares(ContainerInterface $container): array
    {
        $middlewares = [];
        foreach (Application::getInstance()->getConfig()->get('application.tars.client.middleware', []) as $middleware) {
            $middlewares[] = $container->get($middleware);
        }

        return $middlewares;
    }

    private function addTarsRequestLog(): void
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
                        'TarsRequestLogger' => $serverConfiguration->createAccessLogger($path.'/tars-client.log'),
                    ],
                    'logger' => [
                        'TarsRequestLogger' => 'TarsRequestLogger',
                    ],
                ],
                'jsonrpc' => [
                    'client' => [
                        'middleware' => [
                            'tarsRequestLog',
                        ],
                    ],
                ],
            ],
        ]);
    }
}
