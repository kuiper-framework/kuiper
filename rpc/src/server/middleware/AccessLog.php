<?php

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

    public function __construct(RequestLogFormatterInterface $formatter, ?callable $requestFilter = null)
    {
        $this->formatter = $formatter;
        $this->requestFilter = $requestFilter;
    }

    public function process(RpcRequestInterface $request, RpcRequestHandlerInterface $handler): RpcResponseInterface
    {
        $start = microtime(true);
        $response = null;
        try {
            $response = $handler->handle($request);

            return $response;
        } finally {
            if (null === $this->requestFilter || (bool) call_user_func($this->requestFilter, $request, $response)) {
                $responseTime = (microtime(true) - $start) * 1000;
                $this->logger->info(...$this->formatter->format($request, $response, $responseTime));
            }
        }
    }
}
