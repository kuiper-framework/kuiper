<?php

declare(strict_types=1);

namespace kuiper\jsonrpc\client;

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
use kuiper\rpc\RpcMethodFactoryInterface;
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
     * @var RpcMethodFactoryInterface|null
     */
    private $rpcMethodFactory;

    /**
     * @var RpcRequestFactoryInterface|null
     */
    private $rpcRequestFactory;

    /**
     * @var RpcResponseFactoryInterface|null
     */
    private $rpcResponseFactory;

    /**
     * @var RpcResponseFactoryInterface|null
     */
    private $noOutParamRpcResponseFactory;

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

    protected function getRpcMethodFactory(): RpcMethodFactoryInterface
    {
        if (null === $this->rpcMethodFactory) {
            $this->rpcMethodFactory = new JsonRpcMethodFactory();
        }

        return $this->rpcMethodFactory;
    }

    protected function getRpcRequestFactory(): RpcRequestFactoryInterface
    {
        if (null === $this->rpcRequestFactory) {
            $this->rpcRequestFactory = new JsonRpcRequestFactory($this->httpRequestFactory, $this->streamFactory, $this->getRpcMethodFactory());
        }

        return $this->rpcRequestFactory;
    }

    protected function getNoOutParamRpcResponseFactory(): RpcResponseFactoryInterface
    {
        if (null === $this->noOutParamRpcResponseFactory) {
            $this->noOutParamRpcResponseFactory = new NoOutParamJsonRpcResponseFactory($this->rpcResponseNormalizer, $this->exceptionNormalizer);
        }

        return $this->noOutParamRpcResponseFactory;
    }

    protected function getRpcResponseFactory(): RpcResponseFactoryInterface
    {
        if (null === $this->rpcResponseFactory) {
            $this->rpcResponseFactory = new JsonRpcResponseFactory($this->rpcResponseNormalizer, $this->exceptionNormalizer);
        }

        return $this->rpcResponseFactory;
    }

    protected function createRpcResponseFactory(bool $outParam): RpcResponseFactoryInterface
    {
        return $outParam ? $this->getRpcResponseFactory() : $this->getNoOutParamRpcResponseFactory();
    }

    protected function createHttpRpcExecutorFactory(array $options): RpcExecutorFactoryInterface
    {
        $responseFactory = $this->createRpcResponseFactory($options['out_params'] ?? false);
        $transporter = new HttpTransporter($this->httpClientFactory->create($options));
        $rpcClient = new RpcClient($transporter, $responseFactory);

        return new RpcExecutorFactory($this->getRpcRequestFactory(), $rpcClient, $this->middlewares);
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
            $transporter = new $connectionClass($this->httpResponseFactory, null, $options);
            $transporter->setLogger($logger);

            return $transporter;
        }));
        $rpcClient = new RpcClient($transporter, $responseFactory);

        return new RpcExecutorFactory($this->getRpcRequestFactory(), $rpcClient, $this->middlewares);
    }

    /**
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

        if (isset($options['protocol'])) {
            $protocol = $options['protocol'];
        } elseif (isset($options['base_uri'])) {
            $protocol = ServerType::HTTP;
        } elseif (isset($options['endpoint'])) {
            $endpoint = Endpoint::fromString($options['endpoint']);
            $protocol = $endpoint->getProtocol();
        } else {
            $protocol = ServerType::HTTP;
        }
        if (ServerType::TCP === $protocol) {
            $rpcExecutorFactory = $this->createTcpRpcExecutorFactory($className, $options);
        } else {
            $rpcExecutorFactory = $this->createHttpRpcExecutorFactory($options);
        }

        return new $class($rpcExecutorFactory);
    }
}
