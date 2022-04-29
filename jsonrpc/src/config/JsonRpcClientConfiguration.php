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
use kuiper\di\annotation\Bean;
use kuiper\di\ComponentCollection;
use kuiper\di\ContainerBuilderAwareTrait;
use kuiper\di\DefinitionConfiguration;
use kuiper\helper\Arrays;
use kuiper\helper\Text;
use kuiper\http\client\HttpClientFactoryInterface;
use kuiper\jsonrpc\annotation\JsonRpcClient;
use kuiper\jsonrpc\annotation\JsonRpcService;
use kuiper\jsonrpc\client\JsonRpcClientFactory;
use kuiper\logger\LoggerConfiguration;
use kuiper\logger\LoggerFactoryInterface;
use kuiper\resilience\core\SwooleAtomicCounter;
use kuiper\rpc\client\ProxyGenerator;
use kuiper\rpc\client\ProxyGeneratorInterface;
use kuiper\rpc\client\RequestIdGenerator;
use kuiper\rpc\client\RequestIdGeneratorInterface;
use kuiper\rpc\JsonRpcRequestLogFormatter;
use kuiper\rpc\server\middleware\AccessLog;
use kuiper\rpc\transporter\Endpoint;
use kuiper\swoole\Application;
use kuiper\swoole\constants\ServerType;
use kuiper\swoole\logger\RequestLogFormatterInterface;
use Psr\Container\ContainerInterface;

class JsonRpcClientConfiguration implements DefinitionConfiguration
{
    use ContainerBuilderAwareTrait;

    public function getDefinitions(): array
    {
        $this->addJsonRpcRequestLog();
        Application::getInstance()->getConfig()->merge([
            'application' => [
                'jsonrpc' => [
                    'client' => [
                        'middleware' => [
                            'jsonrpcRequestLog',
                        ],
                    ],
                ],
            ],
        ]);

        return array_merge($this->createJsonRpcClients(), [
            ProxyGeneratorInterface::class => autowire(ProxyGenerator::class),
            'jsonrpcClientRequestLogFormatter' => autowire(JsonRpcRequestLogFormatter::class),
            JsonRpcClientFactory::class => autowire(JsonRpcClientFactory::class)
                ->constructorParameter('middlewares', get('jsonrpcClientMiddlewares'))
                ->constructorParameter('httpClientFactory', factory(function (ContainerInterface $container) {
                    return $container->has(HttpClientFactoryInterface::class) ? $container->get(HttpClientFactoryInterface::class) : null;
                })),
        ]);
    }

    /**
     * @Bean
     */
    public function requestIdGenerator(): RequestIdGeneratorInterface
    {
        return new RequestIdGenerator(new SwooleAtomicCounter());
    }

    /**
     * @Bean("jsonrpcRequestLog")
     * @Inject({"requestLogFormatter": "jsonrpcClientRequestLogFormatter"})
     */
    public function jsonrpcRequestLog(RequestLogFormatterInterface $requestLogFormatter, LoggerFactoryInterface $loggerFactory): AccessLog
    {
        $middleware = new AccessLog($requestLogFormatter);
        $middleware->setLogger($loggerFactory->create('JsonRpcRequestLogger'));

        return $middleware;
    }

    /**
     * options:
     *  - middleware
     *  - class
     *  - service
     *  - version.
     */
    private function createJsonRpcClients(): array
    {
        $definitions = [];
        $config = Application::getInstance()->getConfig();
        $options = $config->get('application.jsonrpc.client.options', []);
        $createClient = function (ContainerInterface $container, array $options) use ($config) {
            $options['protocol'] = $this->getProtocol($options);
            if (ServerType::fromValue($options['protocol'])->isHttpProtocol()) {
                $options = array_merge($config->get('application.jsonrpc.client.http_options', []), $options);
            } else {
                $options = array_merge($config->get('application.jsonrpc.client.tcp_options', []), $options);
            }
            if (isset($options['middleware'])) {
                foreach ($options['middleware'] as $i => $middleware) {
                    if (is_string($middleware)) {
                        $options['middleware'][$i] = $container->get($middleware);
                    }
                }
            }

            return $container->get(JsonRpcClientFactory::class)->create($options['class'], $options);
        };
        $jsonrpcServices = $this->getServices();
        /** @var JsonRpcClient $annotation */
        foreach (ComponentCollection::getComponents(JsonRpcClient::class) as $annotation) {
            if (isset($jsonrpcServices[$annotation->getTargetClass()])) {
                continue;
            }
            $name = $annotation->getComponentId();
            $clientOptions = array_merge(
                Arrays::mapKeys(get_object_vars($annotation), [Text::class, 'snakeCase']),
                $options[$name] ?? []
            );
            $clientOptions['class'] = $annotation->getTargetClass();
            $definitions[$name] = factory(function (ContainerInterface $container) use ($createClient, $clientOptions) {
                return $createClient($container, $clientOptions);
            });
        }

        foreach ($config->get('application.jsonrpc.client.clients', []) as $name => $service) {
            $componentId = is_string($name) ? $name : $service;
            $clientOptions = array_merge($options[$componentId] ?? []);
            $clientOptions['class'] = $service;
            $definitions[$componentId] = factory(function (ContainerInterface $container) use ($createClient, $clientOptions) {
                return $createClient($container, $clientOptions);
            });
        }

        return $definitions;
    }

    private function getServices(): array
    {
        $services = [];
        /** @var JsonRpcService $annotation */
        foreach (ComponentCollection::getComponents(JsonRpcService::class) as $annotation) {
            $serviceClass = JsonRpcServerConfiguration::getServiceClass($annotation->getTarget());
            $services[$serviceClass] = true;
        }

        return $services;
    }

    private function getProtocol(array $options): string
    {
        if (isset($options['protocol']) && ServerType::hasValue($options['protocol'])) {
            return $options['protocol'];
        }

        if (isset($options['base_uri'])) {
            return ServerType::HTTP;
        }

        if (isset($options['endpoint'])) {
            $endpoint = Endpoint::fromString($options['endpoint']);

            return $endpoint->getProtocol();
        }

        return ServerType::TCP;
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
        $config = Application::getInstance()->getConfig();
        $path = $config->get('application.logging.path');
        if (null === $path) {
            return;
        }
        $config->merge([
            'application' => [
                'logging' => [
                    'loggers' => [
                        'JsonRpcRequestLogger' => LoggerConfiguration::createJsonLogger(
                            $config->getString('application.logging.jsonrpc_client_log_file', $path.'/jsonrpc-client.log')),
                    ],
                    'logger' => [
                        'JsonRpcRequestLogger' => 'JsonRpcRequestLogger',
                    ],
                ],
            ],
        ]);
    }
}
