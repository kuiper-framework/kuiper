<?php

declare(strict_types=1);

namespace kuiper\rpc;

use Psr\Http\Message\RequestInterface as HttpRequestInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UriInterface;

class RpcRpcRequest implements RpcRequestInterface
{
    /**
     * @var HttpRequestInterface
     */
    protected $httpRequest;

    /**
     * @var array
     */
    protected $attributes;

    /**
     * @var InvokingMethod
     */
    protected $invokingMethod;

    /**
     * RpcRequest constructor.
     */
    public function __construct(HttpRequestInterface $httpRequest, InvokingMethod $invokingMethod)
    {
        $this->httpRequest = $httpRequest;
        $this->invokingMethod = $invokingMethod;
        $this->attributes = [];
    }

    public function getProtocolVersion()
    {
        return $this->httpRequest->getProtocolVersion();
    }

    public function withProtocolVersion($version)
    {
        $copy = clone $this;
        $copy->httpRequest = $this->httpRequest->withProtocolVersion($version);

        return $copy;
    }

    public function getHeaders()
    {
        return $this->httpRequest->getHeaders();
    }

    public function hasHeader($name)
    {
        return $this->httpRequest->hasHeader($name);
    }

    public function getHeader($name)
    {
        return $this->httpRequest->getHeader($name);
    }

    public function getHeaderLine($name)
    {
        return $this->httpRequest->getHeaderLine($name);
    }

    public function withHeader($name, $value)
    {
        $copy = clone $this;
        $copy->httpRequest = $this->httpRequest->withHeader($name, $value);

        return $copy;
    }

    public function withAddedHeader($name, $value)
    {
        $copy = clone $this;
        $copy->httpRequest = $this->httpRequest->withAddedHeader($name, $value);

        return $copy;
    }

    public function withoutHeader($name)
    {
        $copy = clone $this;
        $copy->httpRequest = $this->httpRequest->withoutHeader($name);

        return $copy;
    }

    public function getBody()
    {
        return $this->httpRequest->getBody();
    }

    public function withBody(StreamInterface $body)
    {
        $copy = clone $this;
        $copy->httpRequest = $this->httpRequest->withBody($body);

        return $copy;
    }

    public function getRequestTarget()
    {
        return $this->httpRequest->getRequestTarget();
    }

    public function withRequestTarget($requestTarget)
    {
        $copy = clone $this;
        $copy->httpRequest = $this->httpRequest->withRequestTarget($requestTarget);

        return $copy;
    }

    public function getMethod()
    {
        return $this->httpRequest->getMethod();
    }

    public function withMethod($method)
    {
        $copy = clone $this;
        $copy->httpRequest = $this->httpRequest->withMethod($method);

        return $copy;
    }

    public function getUri()
    {
        return $this->httpRequest->getUri();
    }

    public function withUri(UriInterface $uri, $preserveHost = false)
    {
        $copy = clone $this;
        $copy->httpRequest = $this->httpRequest->withUri($uri, $preserveHost);

        return $copy;
    }

    public function getAttributes(): array
    {
        return $this->attributes;
    }

    /**
     * {@inheritDoc}
     */
    public function getAttribute(string $name, $default = null)
    {
        if (false === array_key_exists($name, $this->attributes)) {
            return $default;
        }

        return $this->attributes[$name];
    }

    /**
     * {@inheritDoc}
     */
    public function withAttribute(string $name, $value)
    {
        $new = clone $this;
        $new->attributes[$name] = $value;

        return $new;
    }

    public function getInvokingMethod(): InvokingMethod
    {
        return $this->invokingMethod;
    }
}
