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

use Nyholm\Psr7\Factory\Psr17Factory;
use Nyholm\Psr7\ServerRequest;
use Psr\Http\Message\ServerRequestInterface;
use Swoole\Http\Request;

class NyholmSwooleRequestBridge implements SwooleRequestBridgeInterface
{
    /**
     * {@inheritdoc}
     */
    public function create(Request $swooleRequest): ServerRequestInterface
    {
        $factory = new Psr17Factory();
        $server = MessageUtil::extractServerParams($swooleRequest);
        $headers = $swooleRequest->header;
        $serverRequest = new ServerRequest(
            method: $server['REQUEST_METHOD'],
            uri: MessageUtil::extractUri($factory->createUri(), $server),
            headers: $headers,
            body: $swooleRequest->rawContent(),
            serverParams: $server
        );

        return MessageUtil::extractServerRequest($swooleRequest, $serverRequest, $factory, $factory);
    }
}
