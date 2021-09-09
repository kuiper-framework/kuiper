<?php

declare(strict_types=1);

namespace kuiper\jsonrpc\client;

use kuiper\annotations\AnnotationReaderInterface;
use kuiper\http\client\HttpClientFactoryInterface;
use kuiper\jsonrpc\core\JsonRpcProtocol;
use kuiper\logger\LoggerFactoryInterface;
use kuiper\rpc\client\ProxyGeneratorInterface;
use kuiper\rpc\client\RpcClient;
use kuiper\rpc\client\RpcExecutorFactory;
use kuiper\rpc\client\RpcExecutorFactoryInterface;
use kuiper\rpc\client\RpcRequestFactoryInterface;
use kuiper\rpc\client\RpcResponseFactoryInterface;
use kuiper\rpc\client\RpcResponseNormalizer;
use kuiper\rpc\MiddlewareInterface;
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

class JsonRpcClientFactory implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    /**
     * @var AnnotationReaderInterface
     */
    private $annotationReader;

    /**
     * @var RpcResponseNormalizer
     */
    private $rpcResponseNormalizer;
    /**
     * @var ExceptionNormalizer
     */
    private $exceptionNormalizer;

    /**
     * @var MiddlewareInterface[]
     */
    private $middlewares;

    /**
     * @var ResponseFactoryInterface
     */
    private $httpResponseFactory;

    /**
     * @var StreamFactoryInterface
     */
    private $streamFactory;

    /**
     * @var RequestFactoryInterface
     */
    private $httpRequestFactory;

    /**
     * @var ProxyGeneratorInterface
     */
    private $proxyGenerator;

    /**
     * @var LoggerFactoryInterface
     */
    private $loggerFactory;

    /**
     * @var PoolFactoryInterface
     */
    private $poolFactory;

    /**
     * @var HttpClientFactoryInterface|null
     */
    private $httpClientFactory;

    /**
     * JsonRpcClientFactory constructor.
     *
     * @param RpcResponseNormalizer    $rpcResponseNormalizer
     * @param ExceptionNormalizer      $exceptionNormalizer
     * @param MiddlewareInterface[]    $middlewares
     * @param ResponseFactoryInterface $httpResponseFactory
     * @param StreamFactoryInterface   $streamFactory
     * @param RequestFactoryInterface  $httpRequestFactory
     * @param ProxyGeneratorInterface  $proxyGenerator
     * @param LoggerFactoryInterface   $loggerFactory
     * @param PoolFactoryInterface     $poolFactory
     */
    public function __construct(
        AnnotationReaderInterface $annotationReader,
        RpcResponseNormalizer $rpcResponseNormalizer,
        ExceptionNormalizer $exceptionNormalizer,
        array $middlewares,
        ResponseFactoryInterface $httpResponseFactory,
        StreamFactoryInterface $streamFactory,
        RequestFactoryInterface $httpRequestFactory,
        ProxyGeneratorInterface $proxyGenerator,
        LoggerFactoryInterface $loggerFactory,
        PoolFactoryInterface $poolFactory,
        HttpClientFactoryInterface $httpClientFactory = null)
    {
        $this->annotationReader = $annotationReader;
        $this->rpcResponseNormalizer = $rpcResponseNormalizer;
        $this->exceptionNormalizer = $exceptionNormalizer;
        $this->middlewares = $middlewares;
        $this->httpResponseFactory = $httpResponseFactory;
        $this->streamFactory = $streamFactory;
        $this->httpRequestFactory = $httpRequestFactory;
        $this->proxyGenerator = $proxyGenerator;
        $this->loggerFactory = $loggerFactory;
        $this->poolFactory = $poolFactory;
        $this->httpClientFactory = $httpClientFactory;
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
            new JsonRpcMethodFactory($this->annotationReader, [
                $className => $options,
            ]),
            $options['base_uri'] ?? $options['endpoint'] ?? '/'
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
        $transporter = new PooledTransporter($this->poolFactory->create($className, function ($connId) use ($logger, $className, $options): TransporterInterface {
            $options = array_merge([
                ClientSettings::OPEN_LENGTH_CHECK => false,
                ClientSettings::OPEN_EOF_CHECK => true,
                ClientSettings::PACKAGE_EOF => JsonRpcProtocol::EOF,
            ], $options);
            $connectionClass = Coroutine::isEnabled() ? SwooleCoroutineTcpTransporter::class : SwooleTcpTransporter::class;
            $logger->info("[$className] create connection $connId", ['class' => $connectionClass]);
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
     * @throws \ReflectionException
     */
    public function create(string $className, array $options)
    {
        $proxyClass = $this->proxyGenerator->generate($className);
        $proxyClass->eval();
        $class = $proxyClass->getClassName();
        if (isset($options['endpoint'])) {
            // Laminas\Diactoros\Uri cannot accept tcp scheme
            $options['endpoint'] = Endpoint::removeScheme($options['endpoint']);
        }

        if (ServerType::TCP === ($options['protocol'] ?? ServerType::TCP)) {
            $rpcExecutorFactory = $this->createTcpRpcExecutorFactory($className, $options);
        } else {
            $rpcExecutorFactory = $this->createHttpRpcExecutorFactory($className, $options);
        }

        return new $class($rpcExecutorFactory);
    }
}
