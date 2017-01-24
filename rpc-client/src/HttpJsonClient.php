<?php
namespace kuiper\rpc\client;

use kuiper\serializer\NormalizerInterface;
use kuiper\annotations\DocReaderInterface;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\RequestException;

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
    
    public function sendRequest($requestBody)
    {
        $retry = 0;
        SEND_REQUEST: {
            try {
                $response = $this->httpClient->request('POST', '/', [
                    'headers' => [
                        'Content-Type' => 'application/json',
                        'Accept' => 'application/json'
                    ],
                    'body' => $requestBody
                ]);
                return $response->getBody();
            } catch (RequestException $e) {
                $retry++;
                if ($retry < 3) {
                    goto SEND_REQUEST;
                }
                throw $e;
            }
        }
    }

    public function setHttpClient(ClientInterface $client)
    {
        $this->httpClient = $client;

        return $this;
    }

    public function getHttpClient()
    {
        return $this->httpClient;
    }
}
