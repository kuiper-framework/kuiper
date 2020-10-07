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
     * HttpClientProxy constructor.
     */
    public function __construct(ClientInterface $httpClient, MethodMetadataFactory $methodMetadataFactory)
    {
        $this->httpClient = $httpClient;
        $this->methodMetadataFactory = $methodMetadataFactory;
    }

    public function call(string $clientClass, string $method, ...$args)
    {
        $methodMetadata = $this->methodMetadataFactory->create($clientClass, $method, $args);
        try {
            $response = $this->httpClient->request(
                $methodMetadata->getHttpMethod(),
                $methodMetadata->getUri(),
                $methodMetadata->getOptions()
            );

            return $methodMetadata->deserialize($response);
        } catch (RequestException $e) {
            return $methodMetadata->handleError($e);
        }
    }
}
