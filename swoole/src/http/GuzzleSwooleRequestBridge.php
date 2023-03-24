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

use GuzzleHttp\Psr7\HttpFactory;
use GuzzleHttp\Psr7\ServerRequest;
use Psr\Http\Message\ServerRequestInterface;
use Swoole\Http\Request;

class GuzzleSwooleRequestBridge implements SwooleRequestBridgeInterface
{
    /**
     * {@inheritdoc}
     */
    public function create(Request $swooleRequest): ServerRequestInterface
    {
        $factory = new HttpFactory();
        $server = MessageUtil::extractServerParams($swooleRequest);
        $serverRequest = new ServerRequest(
            method: $server['REQUEST_METHOD'],
            uri: MessageUtil::extractUri($factory->createUri(), $server),
            headers: $swooleRequest->header,
            body: $swooleRequest->rawContent(),
            serverParams: $server
        );

        return MessageUtil::extractServerRequest($swooleRequest, $serverRequest, $factory, $factory);
    }
}
