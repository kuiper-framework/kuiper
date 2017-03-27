<?php

namespace kuiper\rpc\client;

use GuzzleHttp\ClientInterface as HttpClient;
use kuiper\rpc\RequestInterface;
use kuiper\rpc\ResponseInterface;

class HttpHandler implements HandlerInterface
{
    /**
     * @var HttpClient
     */
    private $httpClient;

    /**
     * @var string
     */
    private $uri;

    public function __construct(HttpClient $httpClient, $uri = '/')
    {
        $this->httpClient = $httpClient;
        $this->uri = $uri;
    }

    /**
     * {@inheritdoc}
     */
    public function handle(RequestInterface $request, ResponseInterface $response)
    {
        $httpResponse = $this->httpClient->request('POST', $this->uri, [
            'headers' => [
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ],
            'body' => (string) $request->getBody(),
        ]);

        return $response->withBody($httpResponse->getBody());
    }
}
