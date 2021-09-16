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
use GuzzleHttp\Psr7\Utils;
use Psr\Http\Message\ServerRequestInterface;
use Swoole\Http\Request;

class GuzzleSwooleRequestBridge implements SwooleRequestBridgeInterface
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

        $factory = new HttpFactory();
        $serverRequest = $factory->createServerRequest($server['REQUEST_METHOD'], $server['REQUEST_URI'], $server);
        if (!empty($swooleRequest->files)) {
            $serverRequest->withUploadedFiles(ServerRequest::normalizeFiles($swooleRequest->files));
        }
        if (!empty($swooleRequest->get)) {
            $serverRequest = $serverRequest->withQueryParams($swooleRequest->get);
        }
        if (!empty($swooleRequest->post)) {
            $serverRequest = $serverRequest->withParsedBody($swooleRequest->post);
        }
        if (!empty($swooleRequest->cookie)) {
            $serverRequest = $serverRequest->withCookieParams($swooleRequest->cookie);
        }
        $body = $swooleRequest->rawContent();
        if (!empty($body)) {
            $serverRequest = $serverRequest->withBody(Utils::streamFor($body));
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
