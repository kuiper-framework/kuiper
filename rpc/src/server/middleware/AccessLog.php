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
use kuiper\swoole\logger\RequestLogFormatterInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;

class AccessLog implements MiddlewareInterface, LoggerAwareInterface
{
    use LoggerAwareTrait;

    /**
     * @var RequestLogFormatterInterface
     */
    private $formatter;
    /**
     * @var callable|null
     */
    private $requestFilter;

    public function __construct(RequestLogFormatterInterface $formatter, callable $requestFilter = null)
    {
        $this->formatter = $formatter;
        $this->requestFilter = $requestFilter;
    }

    /**
     * {@inheritDoc}
     */
    public function process(RpcRequestInterface $request, RpcRequestHandlerInterface $handler): RpcResponseInterface
    {
        $start = microtime(true);
        try {
            $response = $handler->handle($request);
            if (null === $this->requestFilter || (bool) call_user_func($this->requestFilter, $request, $response)) {
                $this->logger->info(...$this->formatter->format($request, $response, null, $start, microtime(true)));
            }

            return $response;
        } catch (\Exception $error) {
            $this->logger->info(...$this->formatter->format($request, null, $error, $start, microtime(true)));
            throw $error;
        }
    }
}
