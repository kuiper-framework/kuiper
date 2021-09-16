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

use DI\Annotation\Inject;
use function DI\autowire;
use function DI\factory;
use function DI\get;
use kuiper\annotations\AnnotationReaderInterface;
use kuiper\di\annotation\Bean;
use kuiper\di\ComponentCollection;
use kuiper\helper\PropertyResolverInterface;
use kuiper\helper\Text;
use kuiper\jsonrpc\annotation\JsonRpcService;
use kuiper\jsonrpc\core\JsonRpcProtocol;
use kuiper\jsonrpc\server\JsonRpcServerFactory;
use kuiper\jsonrpc\server\JsonRpcTcpReceiveEventListener;
use kuiper\logger\LoggerConfiguration;
use kuiper\logger\LoggerFactoryInterface;
use kuiper\rpc\annotation\Ignore;
use kuiper\rpc\JsonRpcRequestLogFormatter;
use kuiper\rpc\server\middleware\AccessLog;
use kuiper\rpc\server\Service;
use kuiper\rpc\ServiceLocator;
use kuiper\swoole\Application;
use kuiper\swoole\config\ServerConfiguration;
use kuiper\swoole\constants\ServerSetting;
use kuiper\swoole\constants\ServerType;
use kuiper\swoole\listener\HttpRequestEventListener;
use kuiper\swoole\logger\RequestLogFormatterInterface;
use kuiper\swoole\ServerConfig;
use kuiper\swoole\ServerPort;
use Psr\Container\ContainerInterface;
use Psr\Http\Server\RequestHandlerInterface;

class JsonRpcServerConfiguration extends ServerConfiguration
{
    public function getDefinitions(): array
    {
        $definitions = parent::getDefinitions();
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
        ]);
    }

    /**
     * @Bean("jsonrpcServices")
     */
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
        foreach ($config->get('application.server.ports') as $port => $portConfig) {
            $serverType = is_string($portConfig) ? $portConfig : $portConfig['protocol'] ?? ServerType::HTTP;
            if (ServerType::fromValue($serverType)->isHttpProtocol()) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return Service[]
     */
    protected function getJsonrpcServices(ContainerInterface $container, ServerConfig $serverConfig, string $serverType, int $weight): array
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
        $annotationReader = $container->get(AnnotationReaderInterface::class);
        $services = [];
        /** @var JsonRpcService $annotation */
        foreach (ComponentCollection::getAnnotations(JsonRpcService::class) as $annotation) {
            $serviceName = $annotation->service ?? $this->getServiceName($annotation->getTarget());
            $services[$serviceName] = new Service(
                new ServiceLocator($serviceName, $annotation->version ?? '1.0', JsonRpcProtocol::NS),
                $container->get($annotation->getComponentId()),
                $this->getMethods($annotation->getTarget(), $annotationReader),
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
            $services[$serviceName] = new Service(
                new ServiceLocator($serviceName, $service['version'] ?? '1.0', JsonRpcProtocol::NS),
                $serviceImpl,
                $this->getMethods($class, $annotationReader),
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
                if (Text::startsWith($class->getShortName(), $shortName)
                    || Text::startsWith($shortName, $class->getShortName())) {
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

    private function getMethods(\ReflectionClass $class, AnnotationReaderInterface $annotationReader): array
    {
        $methods = [];
        $class = new \ReflectionClass(self::getServiceClass($class));
        foreach ($class->getMethods(\ReflectionMethod::IS_PUBLIC) as $method) {
            if (null !== $annotationReader->getMethodAnnotation($method, Ignore::class)) {
                continue;
            }
            $methods[] = $method->getName();
        }

        return $methods;
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
     * @Bean("jsonrpcServerRequestLog")
     * @Inject({"requestLogFormatter": "jsonrpcServerRequestLogFormatter"})
     */
    public function jsonrpcRequestLog(RequestLogFormatterInterface $requestLogFormatter, LoggerFactoryInterface $loggerFactory): AccessLog
    {
        $middleware = new AccessLog($requestLogFormatter);
        $middleware->setLogger($loggerFactory->create('JsonRpcServerRequestLogger'));

        return $middleware;
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
