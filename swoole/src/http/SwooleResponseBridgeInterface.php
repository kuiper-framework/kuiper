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

use Psr\Http\Message\ResponseInterface;
use Swoole\Http\Response;

interface SwooleResponseBridgeInterface
{
    /**
     * send psr-7 http response with swoole response.
     */
    public function update(ResponseInterface $response, Response $swooleResponse, bool $withContent = true): void;
}
