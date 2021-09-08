<?php

declare(strict_types=1);

namespace kuiper\http\client;

use GuzzleHttp\ClientInterface;
use kuiper\annotations\AnnotationReaderInterface;
use kuiper\reflection\ReflectionDocBlockFactory;
use kuiper\reflection\ReflectionDocBlockFactoryInterface;
use kuiper\rpc\client\ProxyGenerator;
use kuiper\rpc\client\RpcClient;
use kuiper\rpc\client\RpcExecutorFactory;
use kuiper\rpc\client\RpcResponseFactoryInterface;
use kuiper\rpc\client\RpcResponseNormalizer;
use kuiper\rpc\RpcMethodFactory;
use kuiper\rpc\transporter\HttpTransporter;
use kuiper\serializer\NormalizerInterface;

class HttpProxyClientFactory
{
    /**
     * @var ClientInterface
     */
    private $httpClient;
    /**
     * @var AnnotationReaderInterface
     */
    private $annotationReader;
    /**
     * @var NormalizerInterface
     */
    private $normalizer;

    /**
     * @var RpcResponseFactoryInterface|null
     */
    private $rpcResponseFactory;
    /**
     * @var ReflectionDocBlockFactoryInterface|null
     */
    private $reflectionDocBlockFactory;

    /**
     * HttpProxyClientFactory constructor.
     *
     * @param ClientInterface           $httpClient
     * @param AnnotationReaderInterface $annotationReader
     * @param NormalizerInterface       $normalizer
     */
    public function __construct(ClientInterface $httpClient, AnnotationReaderInterface $annotationReader, NormalizerInterface $normalizer)
    {
        $this->httpClient = $httpClient;
        $this->annotationReader = $annotationReader;
        $this->normalizer = $normalizer;
    }

    /**
     * @return ReflectionDocBlockFactoryInterface|null
     */
    public function getReflectionDocBlockFactory(): ?ReflectionDocBlockFactoryInterface
    {
        if (null === $this->reflectionDocBlockFactory) {
            $this->reflectionDocBlockFactory = ReflectionDocBlockFactory::getInstance();
        }

        return $this->reflectionDocBlockFactory;
    }

    /**
     * @param ReflectionDocBlockFactoryInterface|null $reflectionDocBlockFactory
     */
    public function setReflectionDocBlockFactory(?ReflectionDocBlockFactoryInterface $reflectionDocBlockFactory): void
    {
        $this->reflectionDocBlockFactory = $reflectionDocBlockFactory;
    }

    /**
     * @return RpcResponseFactoryInterface|null
     */
    public function getRpcResponseFactory(): ?RpcResponseFactoryInterface
    {
        if (null === $this->rpcResponseFactory) {
            $this->rpcResponseFactory = new HttpJsonResponseFactory($this->getRpcResponseNormalizer());
        }

        return $this->rpcResponseFactory;
    }

    public function getRpcResponseNormalizer(): RpcResponseNormalizer
    {
        return new RpcResponseNormalizer($this->normalizer, $this->getReflectionDocBlockFactory());
    }

    /**
     * @param RpcResponseFactoryInterface|null $rpcResponseFactory
     */
    public function setRpcResponseFactory(?RpcResponseFactoryInterface $rpcResponseFactory): void
    {
        $this->rpcResponseFactory = $rpcResponseFactory;
    }

    /**
     * @template T
     *
     * @param class-string<T> $interfaceClass
     *
     * @return T
     *
     * @throws \ReflectionException
     */
    public function create(string $interfaceClass)
    {
        $proxyGenerator = new ProxyGenerator($this->getReflectionDocBlockFactory());
        $generatedClass = $proxyGenerator->generate($interfaceClass);
        $generatedClass->eval();
        $class = $generatedClass->getClassName();

        $rpcClient = new RpcClient(new HttpTransporter($this->httpClient), $this->getRpcResponseFactory());
        $requestFactory = new HttpRpcRequestFactory($this->annotationReader, $this->normalizer, new RpcMethodFactory());
        $rpcExecutorFactory = new RpcExecutorFactory($requestFactory, $rpcClient);
        /** @phpstan-ignore-next-line */
        return new $class($rpcExecutorFactory);
    }
}
