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

use InvalidArgumentException;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\UploadedFileFactoryInterface;
use Psr\Http\Message\UploadedFileInterface;
use Psr\Http\Message\UriInterface;
use Swoole\Http\Request;

class MessageUtil
{
    public static function extractServerRequest(Request $swooleRequest, ServerRequestInterface $serverRequest, UploadedFileFactoryInterface $fileFactory, StreamFactoryInterface $streamFactory): ServerRequestInterface
    {
        if (!empty($swooleRequest->files)) {
            $serverRequest = $serverRequest->withUploadedFiles(MessageUtil::normalizeFiles($swooleRequest->files, $fileFactory, $streamFactory));
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

    public static function normalizeFiles(array $files, UploadedFileFactoryInterface $fileFactory, StreamFactoryInterface $streamFactory): array
    {
        $normalized = [];

        foreach ($files as $key => $value) {
            if (is_array($value) && isset($value['tmp_name'])) {
                $normalized[$key] = self::createUploadedFileFromSpec($value, $fileFactory, $streamFactory);
            } elseif (is_array($value)) {
                $normalized[$key] = self::normalizeFiles($value, $fileFactory, $streamFactory);
            } else {
                throw new InvalidArgumentException('Invalid value in files specification');
            }
        }

        return $normalized;
    }

    private static function createUploadedFileFromSpec(array $value, UploadedFileFactoryInterface $fileFactory, StreamFactoryInterface $streamFactory): array|UploadedFileInterface
    {
        if (is_array($value['tmp_name'])) {
            return self::normalizeNestedFileSpec($value, $fileFactory, $streamFactory);
        }

        return $fileFactory->createUploadedFile(
            $streamFactory->createStreamFromFile($value['tmp_name']),
            (int) $value['size'],
            (int) $value['error'],
            $value['name'],
            $value['type']
        );
    }

    private static function normalizeNestedFileSpec(array $files, UploadedFileFactoryInterface $fileFactory, StreamFactoryInterface $streamFactory): array
    {
        $normalizedFiles = [];

        foreach (array_keys($files['tmp_name']) as $key) {
            $spec = [
                'tmp_name' => $files['tmp_name'][$key],
                'size' => $files['size'][$key],
                'error' => $files['error'][$key],
                'name' => $files['name'][$key],
                'type' => $files['type'][$key],
            ];
            $normalizedFiles[$key] = self::createUploadedFileFromSpec($spec, $fileFactory, $streamFactory);
        }

        return $normalizedFiles;
    }

    /**
     * Converts array to cookie string.
     */
    private static function formatCookieString(array $cookie): string
    {
        return implode('; ', array_map(static function ($key, $value): string {
            return $key.'='.$value;
        }, array_keys($cookie), array_values($cookie)));
    }

    public static function extractServerParams(Request $request): array
    {
        $server = array_change_key_case($request->server, CASE_UPPER);
        $headers = $request->header;
        foreach ($headers as $key => $val) {
            $server['HTTP_'.str_replace('-', '_', strtoupper($key))] = $val;
        }
        $server['HTTP_COOKIE'] = isset($request->cookie) ? self::formatCookieString($request->cookie) : '';

        return $server;
    }

    public static function extractUri(UriInterface $uri, array $server): UriInterface
    {
        $isHttps = false;
        if (array_key_exists('HTTPS', $server)) {
            $isHttps = self::marshalHttpsValue($server['HTTPS']);
        }
        $uri = $uri->withScheme($isHttps ? 'https' : 'http');

        [$host, $port] = self::marshalHostAndPort($server);
        if (!empty($host)) {
            $uri = $uri->withHost($host);
            if (!empty($port)) {
                $uri = $uri->withPort($port);
            }
        }

        $path = self::marshalRequestPath($server);

        // Strip query string
        $path = explode('?', $path, 2)[0];

        $query = '';
        if (isset($server['QUERY_STRING']) && is_scalar($server['QUERY_STRING'])) {
            $query = ltrim((string) $server['QUERY_STRING'], '?');
        }

        $fragment = '';
        if (str_contains($path, '#')) {
            [$path, $fragment] = explode('#', $path, 2);
        }

        return $uri
            ->withPath($path)
            ->withFragment($fragment)
            ->withQuery($query);
    }

    private static function marshalHttpsValue(mixed $https): bool
    {
        if (is_bool($https)) {
            return $https;
        }

        if (!is_string($https)) {
            throw new InvalidArgumentException(sprintf('SAPI HTTPS value MUST be a string or boolean; received %s', gettype($https)));
        }

        return 'on' === strtolower($https);
    }

    private static function marshalHostAndPort(array $server): array
    {
        /** @var array{string, null} $defaults */
        static $defaults = ['', null];

        $host = $server['HTTP_HOST'] ?? '';
        if ('' !== $host) {
            // Ignore obviously malformed host headers:
            // - Whitespace is invalid within a hostname and break the URI representation within HTTP.
            //   non-printable characters other than SPACE and TAB are already rejected by HeaderSecurity.
            // - A comma indicates that multiple host headers have been sent which is not legal
            //   and might be used in an attack where a load balancer sees a different host header
            //   than Diactoros.
            if (!preg_match('/[\\t ,]/', $host)) {
                return self::marshalHostAndPortFromHeader($host);
            }
        }

        if (!isset($server['SERVER_NAME'])) {
            return $defaults;
        }

        $host = (string) $server['SERVER_NAME'];
        $port = isset($server['SERVER_PORT']) ? (int) $server['SERVER_PORT'] : null;

        if (
            !isset($server['SERVER_ADDR'])
            || !preg_match('/^\[[0-9a-fA-F\:]+\]$/', $host)
        ) {
            return [$host, $port];
        }

        // Misinterpreted IPv6-Address
        // Reported for Safari on Windows
        return self::marshalIpv6HostAndPort($server, $port);
    }

    /**
     * @param string|string[] $host
     *
     * @return array
     */
    private static function marshalHostAndPortFromHeader($host): array
    {
        if (is_array($host)) {
            $host = implode(', ', $host);
        }

        $port = null;

        // works for regname, IPv4 & IPv6
        if (preg_match('|\:(\d+)$|', $host, $matches)) {
            $host = substr($host, 0, -1 * (strlen($matches[1]) + 1));
            $port = (int) $matches[1];
        }

        return [$host, $port];
    }

    private static function marshalRequestPath(array $server): string
    {
        // IIS7 with URL Rewrite: make sure we get the unencoded url
        // (double slash problem).
        /** @var string|array<string>|null $iisUrlRewritten */
        $iisUrlRewritten = $server['IIS_WasUrlRewritten'] ?? null;
        /** @var string|array<string> $unencodedUrl */
        $unencodedUrl = $server['UNENCODED_URL'] ?? '';
        if ('1' === $iisUrlRewritten && is_string($unencodedUrl) && '' !== $unencodedUrl) {
            return $unencodedUrl;
        }

        /** @var string|array<string>|null $requestUri */
        $requestUri = $server['REQUEST_URI'] ?? null;

        if (is_string($requestUri)) {
            return preg_replace('#^[^/:]+://[^/]+#', '', $requestUri);
        }

        $origPathInfo = $server['ORIG_PATH_INFO'] ?? '';
        if (!is_string($origPathInfo) || '' === $origPathInfo) {
            return '/';
        }

        return $origPathInfo;
    }

    private static function marshalIpv6HostAndPort(array $server, ?int $port): array
    {
        $host = '['.(string) $server['SERVER_ADDR'].']';
        $port = $port ?? 80;
        $portSeparatorPos = strrpos($host, ':');

        if (false === $portSeparatorPos) {
            return [$host, $port];
        }

        if ($port.']' === substr($host, $portSeparatorPos + 1)) {
            // The last digit of the IPv6-Address has been taken as port
            // Unset the port so the default port can be used
            $port = null;
        }

        return [$host, $port];
    }
}
