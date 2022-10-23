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
        callable $requestFilter = null)
    {
        $this->requestFilter = $requestFilter;
        $this->logContext = new LogContextImpl();
    }

    /**
     * {@inheritDoc}
     */
    public function process(RpcRequestInterface $request, RpcRequestHandlerInterface $handler): RpcResponseInterface
    {
        $this->logContext->setRequest($request);
        try {
            $response = $handler->handle($request);
            if (null === $this->requestFilter || (bool) call_user_func($this->requestFilter, $request, $response)) {
                $this->logContext->setResponse($response);
                $this->logger->info(...$this->formatter->format($this->logContext));
            }

            return $response;
        } catch (\Exception $error) {
            $this->logContext->setError($error);
            $this->logger->info(...$this->formatter->format($this->logContext));
            throw $error;
        }
    }
}
