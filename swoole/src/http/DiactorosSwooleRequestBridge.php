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
        $server = array_change_key_case($swooleRequest->server, CASE_UPPER);
        $headers = $swooleRequest->header;
        foreach ($headers as $key => $val) {
            $server['HTTP_'.str_replace('-', '_', strtoupper($key))] = $val;
        }
        $server['HTTP_COOKIE'] = isset($swooleRequest->cookie) ? $this->cookieString($swooleRequest->cookie) : '';
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

    /**
     * Converts array to cookie string.
     */
    private function cookieString(array $cookie): string
    {
        return implode('; ', array_map(static function ($key, $value): string {
            return $key.'='.$value;
        }, array_keys($cookie), array_values($cookie)));
    }
}
