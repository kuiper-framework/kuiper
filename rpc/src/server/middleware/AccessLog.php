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

namespace kuiper\rpc\server\middleware;

use kuiper\rpc\MiddlewareInterface;
use kuiper\rpc\RpcRequestHandlerInterface;
use kuiper\rpc\RpcRequestInterface;
use kuiper\rpc\RpcResponseInterface;
use kuiper\swoole\logger\LogContextImpl;
use kuiper\swoole\logger\RequestLogFormatterInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Throwable;

class AccessLog implements MiddlewareInterface, LoggerAwareInterface
{
    use LoggerAwareTrait;

    /**
     * @var callable|null
     */
    private $requestFilter;

    private readonly LogContextImpl $logContext;

    public function __construct(
        private readonly RequestLogFormatterInterface $formatter,
        callable $requestFilter = null,
        private readonly float $sampleRate = 1.0)
    {
        $this->requestFilter = $requestFilter;
        $this->logContext = new LogContextImpl();
    }

    /**
     * {@inheritDoc}
     */
    public function process(RpcRequestInterface $request, RpcRequestHandlerInterface $handler): RpcResponseInterface
    {
        $context = $this->logContext->withRequest($request);
        try {
            $response = $handler->handle($request);
            if (null !== $this->requestFilter && !call_user_func($this->requestFilter, $request, $response)) {
                return $response;
            }

            if ($this->sampleRate < 1 && ($this->sampleRate <= 0 || random_int(0, PHP_INT_MAX) / PHP_INT_MAX > $this->sampleRate)) {
                return $response;
            }
            $context->update($response);
            $this->logger->info(...$this->formatter->format($context));

            return $response;
        } catch (Throwable $error) {
            $context->update(null, $error);
            $this->logger->info(...$this->formatter->format($context));
            throw $error;
        }
    }
}
