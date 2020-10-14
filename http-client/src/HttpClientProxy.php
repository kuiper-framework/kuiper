<?php

declare(strict_types=1);

namespace kuiper\http\client;

use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\RequestException;

class HttpClientProxy
{
    /**
     * @var ClientInterface
     */
    private $httpClient;

    /**
     * @var MethodMetadataFactory
     */
    private $methodMetadataFactory;

    /**
     * @var ResponseParser
     */
    private $parser;

    /**
     * HttpClientProxy constructor.
     */
    public function __construct(ClientInterface $httpClient, MethodMetadataFactory $methodMetadataFactory, ResponseParser $parser)
    {
        $this->httpClient = $httpClient;
        $this->methodMetadataFactory = $methodMetadataFactory;
        $this->parser = $parser;
    }

    /**
     * @param mixed ...$args
     *
     * @return mixed
     *
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function call(string $clientClass, string $method, ...$args)
    {
        $methodMetadata = $this->methodMetadataFactory->create($clientClass, $method, $args);
        try {
            $response = $this->httpClient->request(
                $methodMetadata->getHttpMethod(),
                $methodMetadata->getUri(),
                $methodMetadata->getOptions()
            );

            return $this->parser->parse($methodMetadata, $response);
        } catch (RequestException $e) {
            return $this->parser->handleError($methodMetadata, $e);
        }
    }
}
