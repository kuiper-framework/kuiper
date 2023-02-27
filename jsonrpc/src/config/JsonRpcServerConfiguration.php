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

use kuiper\swoole\attribute\BootstrapConfiguration;
use function DI\autowire;
use function DI\factory;
use function DI\get;
use kuiper\di\attribute\Bean;
use kuiper\di\attribute\Configuration;
use kuiper\di\ComponentCollection;
use kuiper\di\ContainerBuilderAwareTrait;
use kuiper\di\DefinitionConfiguration;
use kuiper\helper\PropertyResolverInterface;
use kuiper\jsonrpc\attribute\JsonRpcService;
use kuiper\jsonrpc\core\JsonRpcProtocol;
use kuiper\jsonrpc\server\JsonRpcServerFactory;
use kuiper\jsonrpc\server\JsonRpcTcpReceiveEventListener;
use kuiper\logger\LoggerConfiguration;
use kuiper\rpc\attribute\Ignore;
use kuiper\rpc\JsonRpcRequestLogFormatter;
use kuiper\rpc\server\middleware\AccessLog;
use kuiper\rpc\server\Service;
use kuiper\rpc\ServiceLocatorImpl;
use kuiper\swoole\Application;
use kuiper\swoole\config\ServerConfiguration;
use kuiper\swoole\constants\ServerSetting;
use kuiper\swoole\constants\ServerType;
use kuiper\swoole\listener\HttpRequestEventListener;
use kuiper\swoole\ServerConfig;
use kuiper\swoole\ServerPort;
use Psr\Container\ContainerInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\LoggerInterface;

#[Configuration(dependOn: [ServerConfiguration::class])]
#[BootstrapConfiguration]
class JsonRpcServerConfiguration implements DefinitionConfiguration
{
    use ContainerBuilderAwareTrait;

    protected const TAG = '['.__CLASS__.'] ';

    public function getDefinitions(): array
    {
        $this->addAccessLogger();
        $config = Application::getInstance()->getConfig();
        $config->merge([
            'application' => [
                'jsonrpc' => [
                    'server' => [
                        'middleware' => [
                            'jsonrpcServerRequestLog',
                        ],
                    ],
                ],
            ],
        ]);
        $definitions = [];
        if ($this->jsonrpcOnHttp($config)) {
            $definitions[RequestHandlerInterface::class] = factory([JsonRpcServerFactory::class, 'createHttpRequestHandler']);
            $config->merge([
                'application' => [
                    'listeners' => [
                        HttpRequestEventListener::class,
                    ],
                ],
            ]);
        } else {
            $definitions[JsonRpcTcpReceiveEventListener::class] = factory([JsonRpcServerFactory::class, 'createTcpRequestEventListener']);
            $config->merge([
                'application' => [
                    'listeners' => [
                        JsonRpcTcpReceiveEventListener::class,
                    ],
                    'swoole' => [
                        ServerSetting::OPEN_EOF_SPLIT => true,
                        ServerSetting::PACKAGE_EOF => JsonRpcProtocol::EOF,
                    ],
                ],
            ]);
        }

        return array_merge($definitions, [
            JsonRpcServerFactory::class => factory([JsonRpcServerFactory::class, 'createFromContainer']),
            'jsonrpcServerRequestLogFormatter' => autowire(JsonRpcRequestLogFormatter::class),
            'registerServices' => get('jsonrpcServices'),
            'jsonrpcServerRequestLog' => autowire(AccessLog::class)
                ->constructorParameter(0, get('jsonrpcServerRequestLogFormatter')),
        ]);
    }

    #[Bean('jsonrpcServices')]
    public function jsonrpcServices(ContainerInterface $container, ServerConfig $serverConfig, PropertyResolverInterface $config): array
    {
        $weight = (int) $config->get('application.jsonrpc.server.weight');
        if ($this->jsonrpcOnHttp($config)) {
            return $this->getJsonrpcServices($container, $serverConfig, ServerType::HTTP, $weight);
        }

        return $this->getJsonrpcServices($container, $serverConfig, ServerType::TCP, $weight);
    }

    private function jsonrpcOnHttp(PropertyResolverInterface $config): bool
    {
        if ('http' === $config->get('application.jsonrpc.server.protocol')) {
            return true;
        }
        foreach ($config->get('application.server.ports', []) as $portConfig) {
            $serverType = is_string($portConfig) ? $portConfig : $portConfig['protocol'] ?? ServerType::HTTP->value;
            if (ServerType::from($serverType)->isHttpProtocol()) {
                return true;
            }
        }

        return false;
    }

    protected function getJsonrpcServices(ContainerInterface $container, ServerConfig $serverConfig, ServerType $serverType, int $weight): array
    {
        $serverPort = null;
        foreach ($serverConfig->getPorts() as $port) {
            if ($port->getServerType() === $serverType) {
                $serverPort = $port;
                break;
            }
        }
        if (null === $serverPort) {
            throw new \InvalidArgumentException('Cannot find port use http protocol');
        }
        if ('0.0.0.0' === $serverPort->getHost()) {
            $serverPort = new ServerPort(gethostbyname(gethostname()), $serverPort->getPort(), $serverPort->getServerType());
        }
        $logger = $container->get(LoggerInterface::class);
        $services = [];
        /** @var JsonRpcService $annotation */
        foreach (ComponentCollection::getComponents(JsonRpcService::class) as $annotation) {
            $serviceName = $annotation->getService() ?? $this->getServiceName($annotation->getTarget());
            $logger->info(static::TAG."register jsonrpc service $serviceName which served by ".$annotation->getTargetClass());
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
            $serviceImpl = $container->get($service['class']);
            $class = new \ReflectionClass($serviceImpl);
            if (!is_string($serviceName)) {
                $serviceName = $service['service'] ?? $this->getServiceName($class);
            }
            $logger->info(static::TAG."register jsonrpc service $serviceName which served by ".$service['class']);
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

    public static function getServiceClass(\ReflectionClass $class): string
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
            throw new \InvalidArgumentException('Cannot resolve service name from '.$class->getName());
        }

        return $serviceClass;
    }

    private function getServiceName(\ReflectionClass $class): string
    {
        return str_replace('\\', '.', self::getServiceClass($class));
    }

    private function getMethods(\ReflectionClass $class): array
    {
        $methods = [];
        $class = new \ReflectionClass(self::getServiceClass($class));
        foreach ($class->getMethods(\ReflectionMethod::IS_PUBLIC) as $method) {
            if (count($method->getAttributes(Ignore::class)) > 0) {
                continue;
            }
            $methods[] = $method->getName();
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

    private function addAccessLogger(): void
    {
        $config = Application::getInstance()->getConfig();
        $path = $config->get('application.logging.path');
        if (null === $path) {
            return;
        }
        $config->merge([
            'application' => [
                'logging' => [
                    'loggers' => [
                        'JsonRpcServerRequestLogger' => LoggerConfiguration::createJsonLogger(
                            $config->get('application.logging.jsonrpc_server_log_file', $path.'/jsonrpc-server.log')),
                    ],
                    'logger' => [
                        'JsonRpcServerRequestLogger' => 'JsonRpcServerRequestLogger',
                    ],
                ],
            ],
        ]);
    }
}
