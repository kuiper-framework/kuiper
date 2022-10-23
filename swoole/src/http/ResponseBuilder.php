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

use kuiper\helper\Properties;
use kuiper\helper\Text;
use kuiper\swoole\constants\HttpHeaderName;
use kuiper\swoole\constants\HttpServerSetting;
use kuiper\swoole\server\HttpServer;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;

class ResponseBuilder implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    protected const TAG = '['.__CLASS__.'] ';

    private const END_OF_LINE = "\r\n";

    public function __construct(private readonly Properties $options)
    {
        $this->setLogger(\kuiper\logger\Logger::nullLogger());
    }

    public function build(ServerRequestInterface $request, ResponseInterface $response): string
    {
        if ($this->options->getBool(HttpServerSetting::EXPIRE) && 304 === $response->getStatusCode()) {
            return $this->output($response, '');
        }
        $body = (string) $response->getBody();

        if ($this->options->getBool(HttpServerSetting::GZIP)) {
            $encoding = $request->getHeaderLine(HttpHeaderName::ACCEPT_ENCODING);
            if (!empty($encoding)) {
                $gzipLevel = $this->options->getInt(HttpServerSetting::GZIP_LEVEL, -1);
                if (str_contains($encoding, 'gzip')) {
                    $body = \gzencode($body, $gzipLevel);
                    $response = $response->withHeader(HttpHeaderName::CONTENT_ENCODING, 'gzip');
                } elseif (str_contains($encoding, 'deflate')) {
                    $body = \gzdeflate($body, $gzipLevel);
                    $response = $response->withHeader(HttpHeaderName::CONTENT_ENCODING, 'deflate');
                } else {
                    $this->logger->error(static::TAG."Unsupported compression type : {$encoding}");
                }
            }
        }

        return $this->output($response, $body);
    }

    private function output(ResponseInterface $response, string $body): string
    {
        $headers = $response->getHeaders();
        if (!isset($headers[HttpHeaderName::DATE])) {
            $headers[HttpHeaderName::DATE] = [gmdate('D, d M Y H:i:s T')];
        }
        if (!isset($headers[HttpHeaderName::SERVER])) {
            $headers[HttpHeaderName::SERVER] = [HttpServer::SERVER_NAME];
        }
        if (!isset($headers[HttpHeaderName::CONTENT_LENGTH])) {
            $headers[HttpHeaderName::CONTENT_LENGTH] = [strlen($body)];
        }

        $connection = $response->getHeaderLine(HttpHeaderName::CONNECTION);
        $isKeepAlive = Text::isNotEmpty($connection) ? 0 !== strcasecmp($connection, 'close')
            : $this->options->getBool(HttpServerSetting::KEEPALIVE);
        $headers[HttpHeaderName::KEEPALIVE] = [$isKeepAlive ? 'on' : 'off'];
        $headers[HttpHeaderName::CONNECTION] = [$isKeepAlive ? 'keep-alive' : 'close'];

        $head = sprintf('HTTP/1.1 %d %s', $response->getStatusCode(), $response->getReasonPhrase()).self::END_OF_LINE;
        // Headers
        foreach ($headers as $name => $values) {
            foreach ($values as $value) {
                $head .= HttpHeaderName::getDisplayName($name).': '.$value.self::END_OF_LINE;
            }
        }

        return $head.self::END_OF_LINE.$body;
    }
}
