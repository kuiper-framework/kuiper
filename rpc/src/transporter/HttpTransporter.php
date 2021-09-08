<?php

declare(strict_types=1);

namespace kuiper\rpc\transporter;

use GuzzleHttp\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

class HttpTransporter implements TransporterInterface
{
    /**
     * @var ClientInterface
     */
    private $httpClient;

    /**
     * HttpTransporter constructor.
     */
    public function __construct(ClientInterface $httpClient)
    {
        $this->httpClient = $httpClient;
    }

    public function sendRequest(RequestInterface $request): ResponseInterface
    {
        return $this->httpClient->send($request);
    }
}
