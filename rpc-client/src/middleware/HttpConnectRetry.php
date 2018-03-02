<?php

namespace kuiper\rpc\client\middleware;

use GuzzleHttp\Exception\ConnectException;
use kuiper\rpc\MiddlewareInterface;
use kuiper\rpc\RequestInterface;
use kuiper\rpc\ResponseInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;

class HttpConnectRetry implements MiddlewareInterface, LoggerAwareInterface
{
    use LoggerAwareTrait;

    private $retries;

    public function __construct($retries = 3)
    {
        $this->retries = $retries;
    }

    /**
     * {@inheritdoc}
     */
    public function __invoke(RequestInterface $request, ResponseInterface $response, callable $next)
    {
        $retries = 0;
        while (true) {
            try {
                return $next($request, $response);
            } catch (ConnectException $e) {
                ++$retries;
                if ($retries >= $this->retries) {
                    throw $e;
                } else {
                    $this->logger && $this->logger->warning("Call {$request->getMethod()} failed: ".$e->getMessage());
                }
            }
        }
    }
}
