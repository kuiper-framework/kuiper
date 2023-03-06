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
use kuiper\di\ComponentCollection;
use kuiper\di\ContainerBuilderAwareTrait;
use kuiper\di\DefinitionConfiguration;

use function kuiper\helper\env;

use kuiper\helper\Properties;
use kuiper\helper\Text;
use kuiper\http\client\HttpClientFactoryInterface;
use kuiper\jsonrpc\attribute\JsonRpcClient;
use kuiper\jsonrpc\attribute\JsonRpcService;
use kuiper\jsonrpc\client\JsonRpcClientFactory;
use kuiper\logger\LoggerConfiguration;
use kuiper\logger\LoggerFactoryInterface;
use kuiper\resilience\core\SwooleAtomicCounter;
use kuiper\rpc\client\middleware\AddRequestReferer;
use kuiper\rpc\client\ProxyGenerator;
use kuiper\rpc\client\ProxyGeneratorInterface;
use kuiper\rpc\client\RequestIdGenerator;
use kuiper\rpc\client\RequestIdGeneratorInterface;
use kuiper\rpc\RpcRequestJsonLogFormatter;
use kuiper\rpc\server\middleware\AccessLog;
use kuiper\rpc\transporter\Endpoint;
use kuiper\swoole\Application;
use kuiper\swoole\constants\ServerType;
use Psr\Container\ContainerInterface;

class JsonRpcClientConfiguration implements DefinitionConfiguration
{
    use ContainerBuilderAwareTrait;

    public function getDefinitions(): array
    {
        $config = Application::getInstance()->getConfig();
        $config->mergeIfNotExists([
            'application' => [
                'jsonrpc' => [
                    'client' => [
                        'log_file' => env('JSONRPC_CLIENT_LOG_FILE', '{application.logging.path}/jsonrpc.json'),
                        'log_params' => 'true' === env('JSONRPC_CLIENT_LOG_PARAMS'),
                        'protocol' => env('JSONRPC_CLIENT_PROTOCOL', 'http'),
                    ],
                ],
                'logging' => [
                    'loggers' => [
                        'JsonRpcRequestLogger' => LoggerConfiguration::createJsonLogger('{application.jsonrpc.client.log_file}'),
                    ],
                    'logger' => [
                        'JsonRpcRequestLogger' => 'JsonRpcRequestLogger',
                    ],
                ],
            ],
        ]);
        $config->with('application.jsonrpc.client', function (Properties $value) {
            $value->merge([
                'middleware' => [
                    AddRequestReferer::class,
                    'jsonrpcRequestLog',
                ],
            ]);
        });

        return array_merge($this->createJsonRpcClients(), [
            ProxyGeneratorInterface::class => autowire(ProxyGenerator::class),
            JsonRpcClientFactory::class => autowire(JsonRpcClientFactory::class)
                ->constructorParameter('middlewares', get('jsonrpcClientMiddlewares'))
                ->constructorParameter('httpClientFactory', factory(function (ContainerInterface $container) {
                    return $container->has(HttpClientFactoryInterface::class) ? $container->get(HttpClientFactoryInterface::class) : null;
                })),
            'jsonrpcRequestLog' => autowire(AccessLog::class)
                ->constructorParameter(0, get('jsonrpcClientRequestLogFormatter'))
                ->method('setLogger', factory(function (LoggerFactoryInterface $loggerFactory) {
                    return $loggerFactory->create('JsonRpcRequestLogger');
                })),
        ]);
    }

    #[Bean]
    public function requestIdGenerator(): RequestIdGeneratorInterface
    {
        return new RequestIdGenerator(new SwooleAtomicCounter());
    }

    #[Bean('jsonrpcClientRequestLogFormatter')]
    public function jsonrpcClientRequestLogFormatter(#[Inject('application.jsonrpc.client')] array $config): RpcRequestJsonLogFormatter
    {
        return new RpcRequestJsonLogFormatter(
            fields: RpcRequestJsonLogFormatter::CLIENT,
            extra: !empty($config['log_params']) ? ['params', 'pid'] : ['pid']
        );
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
        $jsonrpcServices = $this->getServices();
        /** @var JsonRpcClient $annotation */
        foreach (ComponentCollection::getComponents(JsonRpcClient::class) as $annotation) {
            if (isset($jsonrpcServices[$annotation->getTargetClass()])) {
                continue;
            }
            $name = $annotation->getComponentId();
            $definitions[$name] = factory(function (JsonRpcClientFactory $factory) use ($annotation) {
                return $this->createJsonRpcClient($factory, $annotation->getTargetClass());
            });
        }

        foreach ($config->get('application.jsonrpc.client.clients', []) as $name => $options) {
            if (is_string($options)) {
                $options = ['class' => $options];
            }
            if (!isset($options['class'])) {
                throw new InvalidArgumentException("application.jsonrpc.client.clients.{$name} class is required");
            }
            $options['name'] = $componentId = is_string($name) ? $name : $options['class'];
            $definitions[$componentId] = factory(function (JsonRpcClientFactory $factory) use ($options) {
                return $this->createJsonRpcClient($factory, $options['class'], $options);
            });
        }

        return $definitions;
    }

    public function createJsonRpcClient(JsonRpcClientFactory $factory, string $clientClass, array $options = []): object
    {
        $config = Application::getInstance()->getConfig()->get('application.jsonrpc.client');
        $clientOptions = $config['options'] ?? [];
        $options = array_merge($options, $clientOptions[$options['name'] ?? $clientClass] ?? []);
        $options['protocol'] = $this->getProtocol($options) ?? $config['protocol'] ?? 'http';
        if (ServerType::from($options['protocol'])->isHttpProtocol()) {
            $options = array_merge($config['http_options'] ?? [], $options);
        } else {
            $options = array_merge($config['tcp_options'] ?? [], $options);
        }

        return $factory->create($clientClass, $options);
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

    private function getProtocol(array $options): ?string
    {
        if (isset($options['protocol']) && null !== ServerType::tryFrom($options['protocol'])) {
            return $options['protocol'];
        }

        if (isset($options['base_uri'])) {
            return ServerType::HTTP->value;
        }

        if (isset($options['endpoint'])) {
            $protocol = Endpoint::fromString($options['endpoint'])->getProtocol();
            if (Text::isNotEmpty($protocol) && null !== ServerType::tryFrom($protocol)) {
                return $protocol;
            }
        }

        return null;
    }

    #[Bean('jsonrpcClientMiddlewares')]
    public function jsonrpcClientMiddlewares(ContainerInterface $container): array
    {
        $middlewares = [];
        foreach (Application::getInstance()->getConfig()->get('application.jsonrpc.client.middleware', []) as $middleware) {
            $middlewares[] = $container->get($middleware);
        }

        return $middlewares;
    }
}
