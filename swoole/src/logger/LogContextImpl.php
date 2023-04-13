<?php

declare(strict_types=1);

/*
 * This file is part of the Kuiper package.
 *
 * (c) Ye Wenbin <wenbinye@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

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

    public function withRequest(RequestInterface $request): self
    {
        $context = clone $this;
        $context->response = null;
        $context->error = null;
        $context->endTime = 0.0;
        $context->request = $request;
        $context->startTime = microtime(true);

        return $context;
    }

    public function update(?ResponseInterface $response, ?Throwable $error = null): void
    {
        $this->response = $response;
        $this->error = $error;
        $this->endTime = microtime(true);
    }

    /**
     * {@inheritDoc}
     */
    public function getRequest(): RequestInterface
    {
        return $this->request;
    }

    /**
     * {@inheritDoc}
     */
    public function getResponse(): ?ResponseInterface
    {
        return $this->response;
    }

    /**
     * {@inheritDoc}
     */
    public function getError(): ?Throwable
    {
        return $this->error;
    }

    /**
     * {@inheritDoc}
     */
    public function getStartTime(): float
    {
        return $this->startTime;
    }

    /**
     * {@inheritDoc}
     */
    public function getEndTime(): float
    {
        return $this->endTime;
    }

    public function hasResponse(): bool
    {
        return null !== $this->response;
    }

    public function hasError(): bool
    {
        return null !== $this->error;
    }

    public function getRequestTime(): float
    {
        return $this->endTime - $this->startTime;
    }
}
