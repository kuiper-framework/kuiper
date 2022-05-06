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

    public function setRequest(RequestInterface $request): void
    {
        $this->request = $request;
        $this->startTime = microtime(true);
    }

    public function setResponse(ResponseInterface $response): void
    {
        $this->response = $response;
        $this->endTime = microtime(true);
    }

    public function setError(Throwable $error): void
    {
        $this->error = $error;
        $this->endTime = microtime(true);
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