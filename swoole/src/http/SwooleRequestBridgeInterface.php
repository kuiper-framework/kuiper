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
