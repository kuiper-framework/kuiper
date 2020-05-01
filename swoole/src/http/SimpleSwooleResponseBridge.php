<?php

declare(strict_types=1);

namespace kuiper\swoole\http;

use Psr\Http\Message\ResponseInterface;
use Swoole\Http\Response;

class SimpleSwooleResponseBridge implements SwooleResponseBridgeInterface
{
    /**
     * {@inheritdoc}
     */
    public function update(ResponseInterface $response, Response $swooleResponse): void
    {
        $swooleResponse->status($response->getStatusCode());
        foreach ($response->getHeaders() as $name => $values) {
            foreach ($values as $value) {
                $swooleResponse->header($name, $value);
            }
        }
        $body = $response->getBody();
        $contentLength = $body->getSize();
        $swooleResponse->header('content-length', (string) $contentLength);

        if ($contentLength > 0) {
            $swooleResponse->write((string) $body);
        }
    }
}
