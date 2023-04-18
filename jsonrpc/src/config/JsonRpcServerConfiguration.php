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

namespace kuiper\jsonrpc\config;

use DI\Attribute\Inject;

use function DI\autowire;
use function DI\factory;
use function DI\get;

use InvalidArgumentException;
use kuiper\di\attribute\Bean;
use kuiper\di\attribute\Configuration;
use kuiper\di\ComponentCollection;
use kuiper\di\ContainerBuilderAwareTrait;
use kuiper\di\DefinitionConfiguration;

use function kuiper\helper\env;

use kuiper\helper\Properties;
use kuiper\helper\PropertyResolverInterface;
use kuiper\jsonrpc\attribute\JsonRpcService;
use kuiper\jsonrpc\core\JsonRpcProtocol;
use kuiper\jsonrpc\server\ErrorHandler;
use kuiper\jsonrpc\server\JsonRpcServerFactory;
use kuiper\logger\LoggerConfiguration;
use kuiper\logger\LoggerFactoryInterface;
use kuiper\rpc\attribute\Ignore;
use kuiper\rpc\RpcRequestJsonLogFormatter;
use kuiper\rpc\server\admin\AdminServant;
use kuiper\rpc\server\admin\AdminServantImpl;
use kuiper\rpc\server\middleware\AccessLog;
use kuiper\rpc\server\middleware\Error;
use kuiper\rpc\server\Service;
use kuiper\rpc\ServiceLocatorImpl;
use kuiper\swoole\Application;
use kuiper\swoole\attribute\BootstrapConfiguration;
use kuiper\swoole\config\ServerConfiguration;
use kuiper\swoole\constants\ServerSetting;
use kuiper\swoole\constants\ServerType;
use kuiper\swoole\listener\HttpRequestEventListener;
use kuiper\swoole\ServerConfig;
use kuiper\swoole\ServerPort;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use ReflectionClass;
use ReflectionMethod;

#[Configuration(dependOn: [ServerConfiguration::class])]
#[BootstrapConfiguration]
class JsonRpcServerConfiguration implements DefinitionConfiguration
{
    use ContainerBuilderAwareTrait;

    protected const TAG = '['.__CLASS__.'] ';

    public function getDefinitions(): array
    {
        $config = Application::getInstance()->getConfig();
        $config->mergeIfNotExists([
            'application' => [
                'server' => [
                    'enable_admin_servant' => 'true' === env('SERVER_ENABLE_ADMIN_SERVANT'),
                ],
                'jsonrpc' => [
                    'server' => [
                        'log_file' => env('JSONRPC_SERVER_LOG_FILE', '{application.logging.path}/jsonrpc-server.json'),
                        'log_params' => 'true' === env('JSONRPC_SERVER_LOG_PARAMS'),
                        'log_sample_rate' => (float) env('JSONRPC_SERVER_LOG_SAMPLE_RATE', '1.0'),
                    ],
                ],
                'logging' => [
                    'loggers' => [
                        'JsonRpcServerRequestLogger' => LoggerConfiguration::createJsonLogger('{application.jsonrpc.server.log_file}'),
                    ],
                    'logger' => [
                        'JsonRpcServerRequestLogger' => 'JsonRpcServerRequestLogger',
                    ],
                ],
            ],
        ]);
        if ($config->getBool('application.server.enable_admin_servant')) {
            $config->mergeIfNotExists([
                'application' => [
                    'jsonrpc' => [
                        'server' => [
                            'services' => [
                                'AdminObj' => AdminServant::class,
                            ],
                        ],
                    ],
                ],
            ]);
        }
        $config->with('application.jsonrpc.server', function (Properties $value) {
            $value->merge([
                'middleware' => [
                    'jsonrpcServerErrorMiddleware',
                    'jsonrpcServerRequestLog',
                ],
            ]);
        });
        $definitions = [];
        $definitions['jsonRpcHttpRequestHandler'] = factory([JsonRpcServerFactory::class, 'createHttpRequestHandler']);
        $definitions['jsonRpcHttpRequestListener'] = autowire(HttpRequestEventListener::class)
            ->constructor(get('jsonRpcHttpRequestHandler'));
        $definitions['jsonRpcTcpReceiveEventListener'] = factory([JsonRpcServerFactory::class, 'createTcpRequestEventListener']);
        if (!$this->jsonrpcOnHttp($config)) {
            $config->merge([
                'application' => [
                    'server' => [
                        'settings' => [
                            ServerSetting::OPEN_EOF_SPLIT => true,
                            ServerSetting::PACKAGE_EOF => JsonRpcProtocol::EOF,
                        ],
                    ],
                ],
            ]);
        }

        return array_merge($definitions, [
            'jsonrpcServerErrorMiddleware' => autowire(Error::class)
                ->constructorParameter(0, get('jsonrpcServerErrorHandler')),
            'jsonrpcServerErrorHandler' => autowire(ErrorHandler::class),
            AdminServant::class => autowire(AdminServantImpl::class),
            JsonRpcServerFactory::class => factory([JsonRpcServerFactory::class, 'createFromContainer']),
            'registerServices' => get('jsonrpcServices'),
        ]);
    }

    private function jsonrpcOnHttp(PropertyResolverInterface $config): bool
    {
        foreach ($config->get('application.server.ports', []) as $portConfig) {
            if (in_array($portConfig['listener'] ?? null, ['jsonRpcTcpReceiveEventListener', 'jsonRpcHttpRequestListener'], true)) {
                $serverType = is_string($portConfig) ? $portConfig : $portConfig['protocol'] ?? ServerType::HTTP->value;
                if (ServerType::from($serverType)->isHttpProtocol()) {
                    return true;
                }
            }
        }

        return false;
    }

    #[Bean('jsonrpcServerRequestLog')]
    public function jsonrpcServerRequestLog(
        #[Inject('jsonrpcServerRequestLogFormatter')] RpcRequestJsonLogFormatter $requestLogFormatter,
        LoggerFactoryInterface $loggerFactory,
        #[Inject('application.jsonrpc.server.log_sample_rate')] float $sampleRate
    ): AccessLog {
        $accessLog = new AccessLog($requestLogFormatter, null, $sampleRate);
        $accessLog->setLogger($loggerFactory->create('JsonRpcServerRequestLogger'));

        return $accessLog;
    }

    #[Bean('jsonrpcServerRequestLogFormatter')]
    public function jsonrpcServerRequestLogFormatter(#[Inject('application.jsonrpc.server')] array $config): RpcRequestJsonLogFormatter
    {
        return new RpcRequestJsonLogFormatter(
            extra: !empty($config['log_params']) ? ['params', 'pid'] : ['pid']
        );
    }

    #[Bean('jsonrpcServices')]
    public function jsonrpcServices(ContainerInterface $container, ServerConfig $serverConfig, PropertyResolverInterface $config): array
    {
        $weight = (int) $config->get('application.jsonrpc.server.weight');
        $serverPort = null;
        foreach ($serverConfig->getPorts() as $port) {
            if (in_array($port->getListener(), ['jsonRpcTcpReceiveEventListener', 'jsonRpcHttpRequestListener'], true)) {
                $serverPort = $port;
                break;
            }
        }
        if (null === $serverPort) {
            throw new InvalidArgumentException('Cannot find port serve jsonrpc service');
        }
        if ('0.0.0.0' === $serverPort->getHost()) {
            $serverPort = new ServerPort(gethostbyname(gethostname()), $serverPort->getPort(), $serverPort->getServerType());
        }
        $logger = $container->get(LoggerInterface::class);
        $services = [];
        /** @var JsonRpcService $annotation */
        foreach (ComponentCollection::getComponents(JsonRpcService::class) as $annotation) {
            $serviceName = $annotation->getService() ?? $this->getServiceName($annotation->getTarget());
            $logger->debug(static::TAG."register jsonrpc service $serviceName which served by ".$annotation->getComponentId());
            $services[$serviceName] = new Service(
                new ServiceLocatorImpl($serviceName, $annotation->getVersion() ?? '1.0', JsonRpcProtocol::NS),
                $container->get($annotation->getComponentId()),
                $this->getMethods($annotation->getTarget()),
                $serverPort,
                $weight
            );
        }
        foreach ($container->get('application.jsonrpc.server.services') ?? [] as $serviceName => $service) {
            if (is_string($service)) {
                $service = ['class' => $service];
            }
            if (!isset($service['class'])) {
                throw new InvalidArgumentException("Missing class for jsonrpc service $serviceName");
            }
            if (!isset($service['implementation'])) {
                $service['implementation'] = $service['class'];
            }
            $serviceImpl = $container->get($service['implementation']);
            $class = new ReflectionClass($service['class']);
            if (!is_string($serviceName)) {
                $serviceName = $service['service'] ?? $this->getServiceName($class);
            }
            $logger->info(static::TAG."register jsonrpc service $serviceName which served by ".$service['implementation']);
            $services[$serviceName] = new Service(
                new ServiceLocatorImpl($serviceName, $service['version'] ?? '1.0', JsonRpcProtocol::NS),
                $serviceImpl,
                $this->getMethods($class),
                $serverPort,
                $weight
            );
        }

        return $services;
    }

    public static function getServiceClass(ReflectionClass $class): string
    {
        if ($class->isInterface()) {
            $serviceClass = $class->getName();
        } else {
            foreach ($class->getInterfaceNames() as $interfaceName) {
                $parts = explode('\\', $interfaceName);
                $shortName = end($parts);
                if (str_starts_with($class->getShortName(), $shortName)
                    || str_starts_with($shortName, $class->getShortName())) {
                    $serviceClass = $interfaceName;
                    break;
                }
            }
        }
        if (!isset($serviceClass)) {
            throw new InvalidArgumentException('Cannot resolve service name from '.$class->getName());
        }

        return $serviceClass;
    }

    private function getServiceName(ReflectionClass $class): string
    {
        return str_replace('\\', '.', self::getServiceClass($class));
    }

    private function getMethods(ReflectionClass $class): array
    {
        $methods = [];
        $class = new ReflectionClass(self::getServiceClass($class));
        foreach ($class->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
            if ($method->isConstructor()
                || $method->isDestructor()
                || $method->isStatic()
                || count($method->getAttributes(Ignore::class)) > 0) {
                continue;
            }
            $methods[$method->getName()] = $method;
        }

        return $methods;
    }

    #[Bean('jsonrpcServerMiddlewares')]
    public function jsonrpcServerMiddlewares(ContainerInterface $container): array
    {
        $middlewares = [];
        foreach (Application::getInstance()->getConfig()->get('application.jsonrpc.server.middleware', []) as $middleware) {
            $middlewares[] = $container->get($middleware);
        }

        return $middlewares;
    }
}
