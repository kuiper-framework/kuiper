<?php

declare(strict_types=1);

namespace kuiper\swoole\http;

use Psr\Http\Message\ResponseInterface;
use Swoole\Http\Response;

interface ResponseSenderInterface
{
    /**
     * send psr-7 http response with swoole response.
     */
    public function send(ResponseInterface $response, Response $swooleResponse): void;
}
