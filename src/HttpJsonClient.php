<?php
namespace kuiper\rpc\client;

use kuiper\serializer\NormalizerInterface;
use kuiper\annotations\DocReaderInterface;
use GuzzleHttp\ClientInterface;

class HttpJsonClient extends AbstractJsonClient
{
    /**
     * @var ClientInterface
     */
    private $httpClient;

    public function __construct(
        ClientInterface $client,
        NormalizerInterface $normalizer,
        DocReaderInterface $docReader,
        array $map = []
    ) {
        $this->httpClient = $client;
        parent::__construct($normalizer, $docReader, $map);
    }
    
    protected function sendRequest($requestBody)
    {
        $response = $this->httpClient->request('POST', '/', [
            'headers' => [
                'Content-Type' => 'application/json',
                'Accept' => 'application/json'
            ],
            'body' => $requestBody
        ]);
        return $response->getBody();
    }
}
