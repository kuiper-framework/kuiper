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
use kuiper\swoole\Application;
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
                        'log_sample_rate' => (float) env('JSONRPC_CLIENT_LOG_SAMPLE_RATE', '1.0'),
                        'protocol' => env('JSONRPC_CLIENT_PROTOCOL', 'http'),
                        'http_options' => [
                            'timeout' => (float) env('JSONRPC_CLIENT_HTTP_TIMEOUT', '5'),
                            'http_errors' => false,
                        ],
                        'tcp_options' => [
                            'timeout' => (float) env('JSONRPC_CLIENT_TCP_TIMEOUT', '5'),
                        ],
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
                ->constructorParameter('defaultOptions', get('application.jsonrpc.client'))
                ->constructorParameter('middlewares', get('jsonrpcClientMiddlewares'))
                ->constructorParameter('httpClientFactory', factory(function (ContainerInterface $container) {
                    return $container->has(HttpClientFactoryInterface::class) ? $container->get(HttpClientFactoryInterface::class) : null;
                })),
        ]);
    }

    #[Bean]
    public function requestIdGenerator(): RequestIdGeneratorInterface
    {
        return new RequestIdGenerator(new SwooleAtomicCounter());
    }

    #[Bean('jsonrpcRequestLog')]
    public function jsonrpcRequestLog(
        #[Inject('jsonrpcClientRequestLogFormatter')] RpcRequestJsonLogFormatter $requestLogFormatter,
        LoggerFactoryInterface $loggerFactory,
        #[Inject('application.jsonrpc.client.log_sample_rate')] float $sampleRate
    ): AccessLog {
        $accessLog = new AccessLog($requestLogFormatter, null, $sampleRate);
        $accessLog->setLogger($loggerFactory->create('JsonRpcRequestLogger'));

        return $accessLog;
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
                return $factory->create($annotation->getTargetClass());
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
                return $factory->create($options['class'], $options);
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
