<?php

declare(strict_types=1);

namespace kuiper\swoole\http;

use Psr\Http\Message\ServerRequestInterface;
use Swoole\Http\Request;

interface ServerRequestFactoryInterface
{
    /**
     * Convert swoole request to PSR-7 http request.
     */
    public function createServerRequest(Request $swooleRequest): ServerRequestInterface;
}
