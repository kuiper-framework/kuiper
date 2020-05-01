<?php

declare(strict_types=1);

namespace kuiper\swoole\http;

use Psr\Http\Message\ServerRequestInterface;
use Swoole\Http\Request;

interface SwooleRequestBridgeInterface
{
    /**
     * Convert swoole request to PSR-7 http request.
     */
    public function create(Request $swooleRequest): ServerRequestInterface;
}
