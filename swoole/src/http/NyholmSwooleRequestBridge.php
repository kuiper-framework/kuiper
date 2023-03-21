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

use Nyholm\Psr7\UploadedFile;
use Psr\Http\Message\ServerRequestInterface;
use Swoole\Http\Request;

class NyholmSwooleRequestBridge implements SwooleRequestBridgeInterface
{
    /**
     * {@inheritdoc}
     */
    public function create(Request $swooleRequest): ServerRequestInterface
    {
        $server = array_change_key_case($swooleRequest->server, CASE_UPPER);
        $headers = $swooleRequest->header;
        foreach ($headers as $key => $val) {
            $server['HTTP_' . str_replace('-', '_', strtoupper($key))] = $val;
        }
        $server['HTTP_COOKIE'] = isset($swooleRequest->cookie) ? $this->cookieString($swooleRequest->cookie) : '';

        $body = $swooleRequest->rawContent();
        $serverRequest = new \Nyholm\Psr7\ServerRequest($server['REQUEST_METHOD'], $server['REQUEST_URI'], $headers, $body, '1.1', $server);
        if (!empty($swooleRequest->files)) {
            $serverRequest = $serverRequest->withUploadedFiles(self::normalizeFiles($swooleRequest->files));
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

        return $serverRequest;
    }

    private static function normalizeFiles(array $files): array
    {
        $normalized = [];

        foreach ($files as $key => $value) {
            if (is_array($value) && isset($value['tmp_name'])) {
                $normalized[$key] = self::createUploadedFileFromSpec($value);
            } elseif (is_array($value)) {
                $normalized[$key] = self::normalizeFiles($value);
            } else {
                throw new \InvalidArgumentException('Invalid value in files specification');
            }
        }

        return $normalized;
    }
    private static function createUploadedFileFromSpec(array $value)
    {
        if (is_array($value['tmp_name'])) {
            return self::normalizeNestedFileSpec($value);
        }

        return new UploadedFile(
            $value['tmp_name'],
            (int) $value['size'],
            (int) $value['error'],
            $value['name'],
            $value['type']
        );
    }

    private static function normalizeNestedFileSpec(array $files = []): array
    {
        $normalizedFiles = [];

        foreach (array_keys($files['tmp_name']) as $key) {
            $spec = [
                'tmp_name' => $files['tmp_name'][$key],
                'size'     => $files['size'][$key],
                'error'    => $files['error'][$key],
                'name'     => $files['name'][$key],
                'type'     => $files['type'][$key],
            ];
            $normalizedFiles[$key] = self::createUploadedFileFromSpec($spec);
        }

        return $normalizedFiles;
    }

    /**
     * Converts array to cookie string.
     */
    private function cookieString(array $cookie): string
    {
        return implode('; ', array_map(static function ($key, $value): string {
            return $key . '=' . $value;
        }, array_keys($cookie), array_values($cookie)));
    }
}
