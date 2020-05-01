<?php

declare(strict_types=1);

namespace kuiper\swoole\http;

use Psr\Http\Message\ResponseInterface;
use Swoole\Http\Response;

interface SwooleResponseBridgeInterface
{
    /**
     * send psr-7 http response with swoole response.
     */
    public function update(ResponseInterface $response, Response $swooleResponse): void;
}
