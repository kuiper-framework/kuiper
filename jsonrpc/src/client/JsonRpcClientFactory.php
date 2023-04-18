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

namespace kuiper\jsonrpc\client;

use kuiper\di\ContainerAwareInterface;
use kuiper\di\ContainerAwareTrait;
use kuiper\helper\EnumHelper;
use kuiper\helper\Properties;
use kuiper\helper\Text;
use kuiper\http\client\HttpClientFactoryInterface;
use kuiper\jsonrpc\attribute\JsonRpcClient;
use kuiper\jsonrpc\core\JsonRpcProtocol;
use kuiper\logger\LoggerFactoryInterface;
use kuiper\reflection\ReflectionType;
use kuiper\rpc\client\ProxyGeneratorInterface;
use kuiper\rpc\client\RequestIdGeneratorInterface;
use kuiper\rpc\client\RpcClient;
use kuiper\rpc\client\RpcExecutorFactory;
use kuiper\rpc\client\RpcExecutorFactoryInterface;
use kuiper\rpc\client\RpcRequestFactoryInterface;
use kuiper\rpc\client\RpcResponseFactoryInterface;
use kuiper\rpc\client\RpcResponseNormalizer;
use kuiper\rpc\transporter\Endpoint;
use kuiper\rpc\transporter\GuzzleHttpTransporter;
use kuiper\rpc\transporter\PooledTransporter;
use kuiper\rpc\transporter\SwooleCoroutineTcpTransporter;
use kuiper\rpc\transporter\SwooleTcpTransporter;
use kuiper\rpc\transporter\TransporterInterface;
use kuiper\serializer\normalizer\ExceptionNormalizer;
use kuiper\swoole\constants\ClientSettings;
use kuiper\swoole\constants\ServerType;
use kuiper\swoole\coroutine\Coroutine;
use kuiper\swoole\pool\PoolFactoryInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use ReflectionClass;
use ReflectionException;

class JsonRpcClientFactory implements LoggerAwareInterface, ContainerAwareInterface
{
    use LoggerAwareTrait;
    use ContainerAwareTrait;

    public function __construct(
        private readonly RpcResponseNormalizer $rpcResponseNormalizer,
        private readonly ExceptionNormalizer $exceptionNormalizer,
        private readonly ResponseFactoryInterface $httpResponseFactory,
        private readonly StreamFactoryInterface $streamFactory,
        private readonly RequestFactoryInterface $httpRequestFactory,
        private readonly ProxyGeneratorInterface $proxyGenerator,
        private readonly LoggerFactoryInterface $loggerFactory,
        private readonly PoolFactoryInterface $poolFactory,
        private readonly RequestIdGeneratorInterface $requestIdGenerator,
        private readonly array $middlewares,
        private readonly array $defaultOptions,
        private readonly ?HttpClientFactoryInterface $httpClientFactory = null
    ) {
    }

    protected function createRpcResponseFactory(): RpcResponseFactoryInterface
    {
        return new JsonRpcResponseFactory($this->rpcResponseNormalizer, $this->exceptionNormalizer);
    }

    protected function createRpcRequestFactory(string $className, array $options): RpcRequestFactoryInterface
    {
        return new JsonRpcRequestFactory(
            $this->httpRequestFactory,
            $this->streamFactory,
            new JsonRpcMethodFactory([
                $className => $options,
            ]),
            $this->requestIdGenerator,
            Endpoint::removeTcpScheme($options['base_uri'] ?? $options['endpoint'] ?? '/')
        );
    }

    protected function createHttpRpcExecutorFactory(string $className, array $options): RpcExecutorFactoryInterface
    {
        $responseFactory = $this->createRpcResponseFactory();
        $transporter = new GuzzleHttpTransporter($this->httpClientFactory->create(array_merge($options, [
            'middleware' => $options['http_middleware'] ?? [],
        ])));
        $rpcClient = new RpcClient($transporter, $responseFactory);

        return new RpcExecutorFactory($this->createRpcRequestFactory($className, $options), $rpcClient, array_merge($options['middleware'] ?? [], $this->middlewares));
    }

    protected function createTcpRpcExecutorFactory(string $className, array $options): RpcExecutorFactoryInterface
    {
        $responseFactory = $this->createRpcResponseFactory();
        $logger = $this->loggerFactory->create($className);
        $transporter = new PooledTransporter($this->poolFactory->create($className, function ($connId) use ($logger, $options): TransporterInterface {
            $options = array_merge([
                ClientSettings::OPEN_LENGTH_CHECK => false,
                ClientSettings::OPEN_EOF_CHECK => true,
                ClientSettings::PACKAGE_EOF => JsonRpcProtocol::EOF,
            ], $options);
            $connectionClass = Coroutine::isEnabled() ? SwooleCoroutineTcpTransporter::class : SwooleTcpTransporter::class;
            $transporter = new $connectionClass($this->httpResponseFactory, $options);
            $transporter->setLogger($logger);

            return $transporter;
        }));
        $rpcClient = new RpcClient($transporter, $responseFactory);

        return new RpcExecutorFactory($this->createRpcRequestFactory($className, $options), $rpcClient, $this->middlewares);
    }

    private function envOptions(string $componentId): array
    {
        $prefix = 'JSONRPC_CLIENT_'.str_replace(['.', '\\'], '_', strtoupper($componentId)).'__';
        $options = [];
        foreach ($_ENV as $key => $value) {
            if (str_starts_with($key, $prefix)) {
                $name = strtoupper(substr($key, strlen($prefix)));
                if (null !== ($setting = EnumHelper::tryFromName($name, JsonRpcClientSettings::class))) {
                    $value = ReflectionType::parse($setting->type())->sanitize($value);
                } elseif (ClientSettings::has($name)) {
                    $value = ReflectionType::parse(ClientSettings::type($name))->sanitize($value);
                }
                $options[strtolower($name)] = $value;
            }
        }

        return $options;
    }

    /**
     * options:
     * - protocol tcp|http
     * - endpoint
     * - middleware
     * - http_middleware
     * tcp options （all swoole client setting）：
     * - timeout
     * http options (all guzzle http client setting).
     *
     * @param string $className
     * @param array  $options
     *
     * @return mixed
     *
     * @throws ReflectionException
     */
    public function create(string $className, array $options = [])
    {
        $proxyClass = $this->proxyGenerator->generate($className);
        $proxyClass->eval();
        $class = $proxyClass->getClassName();
        $config = Properties::create();

        $clientOptions = $this->defaultOptions['options'] ?? [];
        $jsonrpcClientAttributes = (new ReflectionClass($proxyClass))->getAttributes(JsonRpcClient::class);
        if (count($jsonrpcClientAttributes) > 0) {
            /** @var JsonRpcClient $jsonrpcClient */
            $jsonrpcClient = $jsonrpcClientAttributes[0]->newInstance();
            $config->merge($jsonrpcClient->toArray());
        }
        $componentId = $options['name'] ?? $className;
        $config->merge($clientOptions[$componentId] ?? []);
        $config->merge($this->envOptions($componentId));
        $config->merge($options);
        $config['protocol'] = $this->getProtocol($config) ?? $this->defaultOptions['protocol'] ?? 'http';
        if (ServerType::from($config['protocol'])->isHttpProtocol()) {
            $httpOptions = $this->defaultOptions['http_options'] ?? [];
            if (isset($httpOptions['middleware'])) {
                $httpOptions['http_middleware'] = $httpOptions['middleware'];
                unset($httpOptions['middleware']);
            }
            $config->mergeIfNotExists($httpOptions);
        } else {
            $config->mergeIfNotExists($this->defaultOptions['tcp_options'] ?? []);
        }
        if (isset($config['endpoint'])) {
            // Laminas\Diactoros\Uri cannot accept tcp scheme
            $config['endpoint'] = Endpoint::removeTcpScheme($config['endpoint']);
        }
        if (isset($config['middleware'])) {
            foreach ($config['middleware'] as $i => $middleware) {
                if (is_string($middleware)) {
                    $config['middleware'][$i] = $this->container->get($middleware);
                }
            }
        }

        if (ServerType::TCP->value === ($config['protocol'] ?? ServerType::TCP->value)) {
            $rpcExecutorFactory = $this->createTcpRpcExecutorFactory($className, $config->toArray());
        } else {
            $rpcExecutorFactory = $this->createHttpRpcExecutorFactory($className, $config->toArray());
        }

        return new $class($rpcExecutorFactory);
    }

    private function getProtocol(Properties $options): ?string
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
}
