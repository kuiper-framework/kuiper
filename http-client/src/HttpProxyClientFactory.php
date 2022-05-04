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
    private ?RpcResponseFactoryInterface $rpcResponseFactory = null;

    private ?ReflectionDocBlockFactory $reflectionDocBlockFactory = null;

    public function __construct(
        private readonly ClientInterface $httpClient,
        private readonly NormalizerInterface $normalizer)
    {
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
        $requestFactory = new HttpRpcRequestFactory($this->normalizer, new RpcMethodFactory());
        $rpcExecutorFactory = new RpcExecutorFactory($requestFactory, $rpcClient);
        /** @phpstan-ignore-next-line */
        return new $class($rpcExecutorFactory);
    }
}
