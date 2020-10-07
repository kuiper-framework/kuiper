<?php

declare(strict_types=1);

namespace kuiper\http\client;

class HttpClientProxyFactory
{
    /**
     * @var HttpClientFactoryInterface
     */
    private $httpClientFactory;

    /**
     * @var MethodMetadataFactory
     */
    private $methodMetadataFactory;

    /**
     * @var ProxyGenerator
     */
    private $proxyGenerator;

    /**
     * @var array
     */
    private $options;

    /**
     * HttpClientProxyFactory constructor.
     */
    public function __construct(
        HttpClientFactoryInterface $httpClientFactory,
        MethodMetadataFactory $methodMetadataFactory,
        ProxyGenerator $proxyGenerator,
        array $options
    ) {
        $this->httpClientFactory = $httpClientFactory;
        $this->methodMetadataFactory = $methodMetadataFactory;
        $this->proxyGenerator = $proxyGenerator;
        $this->options = $options;
    }

    public function create(string $clientClass): object
    {
        $httpClientProxy = new HttpClientProxy(
            $this->httpClientFactory->create($this->options),
            $this->methodMetadataFactory
        );
        $proxyClass = $this->proxyGenerator->generate($clientClass);

        return new $proxyClass($httpClientProxy);
    }
}
