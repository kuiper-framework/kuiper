<?php

declare(strict_types=1);

namespace kuiper\rpc;

use Psr\Http\Message\StreamInterface;

class RpcResponse implements ResponseInterface
{
    /**
     * @var RequestInterface
     */
    private $request;

    /**
     * @var \Psr\Http\Message\ResponseInterface
     */
    private $httpResponse;

    /**
     * RpcResponse constructor.
     */
    public function __construct(RequestInterface $request, \Psr\Http\Message\ResponseInterface $httpResponse)
    {
        $this->request = $request;
        $this->httpResponse = $httpResponse;
    }

    public function getProtocolVersion()
    {
        return $this->httpResponse->getProtocolVersion();
    }

    public function withProtocolVersion($version)
    {
        $copy = clone $this;
        $copy->httpResponse = $this->httpResponse->withProtocolVersion($version);

        return $copy;
    }

    public function getHeaders()
    {
        return $this->httpResponse->getHeaders();
    }

    public function hasHeader($name)
    {
        return $this->httpResponse->hasHeader($name);
    }

    public function getHeader($name)
    {
        return $this->httpResponse->getHeader($name);
    }

    public function getHeaderLine($name)
    {
        return $this->httpResponse->getHeaderLine($name);
    }

    public function withHeader($name, $value)
    {
        $copy = clone $this;
        $copy->httpResponse = $this->httpResponse->withHeader($name, $value);

        return $copy;
    }

    public function withAddedHeader($name, $value)
    {
        $copy = clone $this;
        $copy->httpResponse = $this->httpResponse->withAddedHeader($name, $value);

        return $copy;
    }

    public function withoutHeader($name)
    {
        $copy = clone $this;
        $copy->httpResponse = $this->httpResponse->withoutHeader($name);

        return $copy;
    }

    public function getBody()
    {
        return $this->httpResponse->getBody();
    }

    public function withBody(StreamInterface $body)
    {
        $copy = clone $this;
        $copy->httpResponse = $this->httpResponse->withBody($body);

        return $copy;
    }

    public function getStatusCode()
    {
        return $this->httpResponse->getStatusCode();
    }

    public function withStatus($code, $reasonPhrase = '')
    {
        $copy = clone $this;
        $copy->httpResponse = $this->httpResponse->withStatus($code, $reasonPhrase);

        return $copy;
    }

    public function getRequest(): RequestInterface
    {
        return $this->request;
    }

    public function getReasonPhrase()
    {
        return $this->httpResponse->getReasonPhrase();
    }
}
