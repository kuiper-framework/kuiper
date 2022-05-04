<?php

namespace kuiper\swoole\logger;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Throwable;

class LogContextImpl implements LogContext
{
    private RequestInterface $request;
    private ?ResponseInterface $response = null;
    private ?Throwable $error = null;
    private float $startTime = 0.0;
    private float $endTime = 0.0;
    /**
     * @param RequestInterface $request
     */
    public function setRequest(RequestInterface $request): void
    {
        $this->request = $request;
    }

    /**
     * @param ResponseInterface|null $response
     */
    public function setResponse(?ResponseInterface $response): void
    {
        $this->response = $response;
    }

    /**
     * @param Throwable|null $error
     */
    public function setError(?Throwable $error): void
    {
        $this->error = $error;
    }

    /**
     * @param float $startTime
     */
    public function setStartTime(float $startTime): void
    {
        $this->startTime = $startTime;
    }

    /**
     * @param float $endTime
     */
    public function setEndTime(float $endTime): void
    {
        $this->endTime = $endTime;
    }

    /**
     * @inheritDoc
     */
    public function getRequest(): RequestInterface
    {
        return $this->request;
    }

    /**
     * @inheritDoc
     */
    public function getResponse(): ?ResponseInterface
    {
        return $this->response;
    }

    /**
     * @inheritDoc
     */
    public function getError(): ?Throwable
    {
        return $this->error;
    }

    /**
     * @inheritDoc
     */
    public function getStartTime(): float
    {
        return $this->startTime;
    }

    /**
     * @inheritDoc
     */
    public function getEndTime(): float
    {
        return $this->endTime;
    }

    public function hasResponse(): bool
    {
        return $this->response !== null;
    }

    public function hasError(): bool
    {
        return $this->error !== null;
    }

    public function getRequestTime(): float
    {
        return $this->endTime - $this->startTime;
    }


}