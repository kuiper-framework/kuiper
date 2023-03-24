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

use function Laminas\Diactoros\normalizeUploadedFiles;

use Laminas\Diactoros\ServerRequestFactory;
use Laminas\Diactoros\Stream;
use Psr\Http\Message\ServerRequestInterface;
use Swoole\Http\Request;

class DiactorosSwooleRequestBridge implements SwooleRequestBridgeInterface
{
    /**
     * {@inheritdoc}
     */
    public function create(Request $swooleRequest): ServerRequestInterface
    {
        $server = MessageUtil::extractServerParams($swooleRequest);
        $serverRequest = ServerRequestFactory::fromGlobals(
            $server,
            $swooleRequest->get,
            $swooleRequest->post,
            $swooleRequest->cookie,
            $swooleRequest->files ? normalizeUploadedFiles($swooleRequest->files) : null
        );
        $body = $swooleRequest->rawContent();
        if (!empty($body)) {
            $stream = new Stream('php://memory', 'w');
            $stream->write($body);
            $serverRequest = $serverRequest->withBody($stream);
        }

        return $serverRequest;
    }
}
