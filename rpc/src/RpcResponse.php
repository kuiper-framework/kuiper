<?php

/*
 * This file is part of the Kuiper package.
 *
 * (c) Ye Wenbin <wenbinye@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace kuiper\rpc;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;

class RpcResponse implements RpcResponseInterface
{
    /**
     * RpcResponse constructor.
     */
    public function __construct(
        private readonly RpcRequestInterface $request,
        private readonly ResponseInterface $httpResponse)
    {
    }

    public function getProtocolVersion(): string
    {
        return $this->httpResponse->getProtocolVersion();
    }

    public function withProtocolVersion($version): RpcResponse
    {
        return new self($this->request, $this->httpResponse->withProtocolVersion($version));
    }

    public function getHeaders(): array
    {
        return $this->httpResponse->getHeaders();
    }

    public function hasHeader($name): bool
    {
        return $this->httpResponse->hasHeader($name);
    }

    public function getHeader($name): array
    {
        return $this->httpResponse->getHeader($name);
    }

    public function getHeaderLine($name): string
    {
        return $this->httpResponse->getHeaderLine($name);
    }

    public function withHeader($name, $value): RpcResponse
    {
        return new self($this->request, $this->httpResponse->withHeader($name, $value));
    }

    public function withAddedHeader($name, $value)
    {
        return new self($this->request, $this->httpResponse->withAddedHeader($name, $value));
    }

    public function withoutHeader($name)
    {
        return new self($this->request, $this->httpResponse->withoutHeader($name));
    }

    public function getBody()
    {
        return $this->httpResponse->getBody();
    }

    public function withBody(StreamInterface $body)
    {
        return new self($this->request, $this->httpResponse->withBody($body));
    }

    public function getStatusCode()
    {
        return $this->httpResponse->getStatusCode();
    }

    public function withStatus($code, $reasonPhrase = '')
    {
        return new self($this->request, $this->httpResponse->withStatus($code, $reasonPhrase));
    }

    public function getReasonPhrase()
    {
        return $this->httpResponse->getReasonPhrase();
    }

    /**
     * @return ResponseInterface
     */
    public function getHttpResponse(): ResponseInterface
    {
        return $this->httpResponse;
    }

    public function getRequest(): RpcRequestInterface
    {
        return $this->request;
    }
}
