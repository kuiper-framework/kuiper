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

use kuiper\http\client\HttpClientFactoryInterface;
use kuiper\jsonrpc\core\JsonRpcProtocol;
use kuiper\logger\LoggerFactoryInterface;
use kuiper\rpc\client\ProxyGeneratorInterface;
use kuiper\rpc\client\RequestIdGeneratorInterface;
use kuiper\rpc\client\RpcClient;
use kuiper\rpc\client\RpcExecutorFactory;
use kuiper\rpc\client\RpcExecutorFactoryInterface;
use kuiper\rpc\client\RpcRequestFactoryInterface;
use kuiper\rpc\client\RpcResponseFactoryInterface;
use kuiper\rpc\client\RpcResponseNormalizer;
use kuiper\rpc\transporter\Endpoint;
use kuiper\rpc\transporter\HttpTransporter;
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
use ReflectionException;

class JsonRpcClientFactory implements LoggerAwareInterface
{
    use LoggerAwareTrait;

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
        private readonly ?HttpClientFactoryInterface $httpClientFactory = null)
    {
    }

    protected function createRpcResponseFactory(bool $outParam): RpcResponseFactoryInterface
    {
        return $outParam
            ? new JsonRpcResponseFactory($this->rpcResponseNormalizer, $this->exceptionNormalizer)
            : new NoOutParamJsonRpcResponseFactory($this->rpcResponseNormalizer, $this->exceptionNormalizer);
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
        $responseFactory = $this->createRpcResponseFactory($options['out_params'] ?? false);
        $transporter = new HttpTransporter($this->httpClientFactory->create($options));
        $rpcClient = new RpcClient($transporter, $responseFactory);

        return new RpcExecutorFactory($this->createRpcRequestFactory($className, $options), $rpcClient, array_merge($options['middleware'] ?? [], $this->middlewares));
    }

    protected function createTcpRpcExecutorFactory(string $className, array $options): RpcExecutorFactoryInterface
    {
        $responseFactory = $this->createRpcResponseFactory($options['out_params'] ?? false);
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

    /**
     * options:
     * - protocol tcp|http
     * - endpoint
     * - middleware
     * - out_params
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
    public function create(string $className, array $options)
    {
        $proxyClass = $this->proxyGenerator->generate($className);
        $proxyClass->eval();
        $class = $proxyClass->getClassName();
        if (isset($options['endpoint'])) {
            // Laminas\Diactoros\Uri cannot accept tcp scheme
            $options['endpoint'] = Endpoint::removeTcpScheme($options['endpoint']);
        }

        if (ServerType::TCP->value === ($options['protocol'] ?? ServerType::TCP->value)) {
            $rpcExecutorFactory = $this->createTcpRpcExecutorFactory($className, $options);
        } else {
            $rpcExecutorFactory = $this->createHttpRpcExecutorFactory($className, $options);
        }

        return new $class($rpcExecutorFactory);
    }
}
