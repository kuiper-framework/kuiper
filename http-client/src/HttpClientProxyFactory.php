<?php

declare(strict_types=1);

namespace kuiper\http\client;

use kuiper\annotations\AnnotationReaderInterface;
use kuiper\http\client\annotation\HttpClient;
use Psr\Container\ContainerInterface;

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
     * @var AnnotationReaderInterface
     */
    private $annotationReader;

    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * HttpClientProxyFactory constructor.
     */
    public function __construct(
        HttpClientFactoryInterface $httpClientFactory,
        MethodMetadataFactory $methodMetadataFactory,
        ProxyGenerator $proxyGenerator,
        AnnotationReaderInterface $annotationReader,
        ContainerInterface $container
    ) {
        $this->httpClientFactory = $httpClientFactory;
        $this->methodMetadataFactory = $methodMetadataFactory;
        $this->proxyGenerator = $proxyGenerator;
        $this->annotationReader = $annotationReader;
        $this->container = $container;
    }

    public function create(string $clientClass, array $options): object
    {
        /** @var HttpClient $httpClientAnnotation */
        $httpClientAnnotation = $this->annotationReader->getClassAnnotation(new \ReflectionClass($clientClass), HttpClient::class);
        $parser = $this->container->get($httpClientAnnotation->parser ?? DefaultResponseParser::class);
        $httpClientProxy = new HttpClientProxy(
            $this->httpClientFactory->create($options),
            $this->methodMetadataFactory,
            $parser
        );
        $proxyClass = $this->proxyGenerator->generate($clientClass);

        return new $proxyClass($httpClientProxy);
    }
}
