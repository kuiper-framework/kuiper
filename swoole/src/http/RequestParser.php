<?php

declare(strict_types=1);

namespace kuiper\swoole\http;

use kuiper\swoole\constants\HttpHeaderName;
use kuiper\swoole\constants\ServerSetting;
use kuiper\swoole\exception\BadHttpRequestException;
use kuiper\swoole\server\HttpServer;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UriInterface;

class RequestParser
{
    public const END_OF_LINE = "\r\n";
    public const END_OF_HEAD = self::END_OF_LINE.self::END_OF_LINE;
    public const HEAD_MAX_LEN = 8192;

    /**
     * @var HttpServer
     */
    private $httpServer;
    /**
     * @var int
     */
    private $clientId;

    /**
     * @var ServerRequestInterface
     */
    private $request;

    /**
     * @var bool
     */
    private $completed;

    /**
     * @var string
     */
    private $head;

    /**
     * @var string
     */
    private $body;

    public function __construct(HttpServer $httpServer, int $clientId)
    {
        $this->httpServer = $httpServer;
        $this->clientId = $clientId;
    }

    /**
     * @throws BadHttpRequestException
     */
    public function receive(string $data): void
    {
        $this->completed = false;
        if (null === $this->body) {
            $this->addHead($data);
        } else {
            $this->body .= $data;
        }
        $this->checkBody();
    }

    public function isCompleted(): bool
    {
        return $this->completed;
    }

    /**
     * @return ServerRequestInterface
     */
    public function getRequest(): ?ServerRequestInterface
    {
        return $this->request;
    }

    /**
     * @throws BadHttpRequestException
     */
    private function addHead(string $data): void
    {
        $buffer = $this->head.$data;

        //HTTP结束符
        $ret = strpos($buffer, self::END_OF_HEAD);
        if (false !== $ret) {
            //没有找到EOF，继续等待数据
            [$this->head, $this->body] = explode(self::END_OF_HEAD, $buffer, 2);
            $this->parseHeader();
        } else {
            $this->head = $buffer;
            if (strlen($this->head) >= self::HEAD_MAX_LEN) {
                throw new BadHttpRequestException('Head exceed max length '.self::HEAD_MAX_LEN);
            }
        }
    }

    /**
     * @throws BadHttpRequestException
     */
    private function parseHeader(): void
    {
        // parts[0] = HTTP头;
        // parts[1] = HTTP主体，GET请求没有body
        $headerLines = explode(self::END_OF_LINE, $this->head);
        if (empty($headerLines)) {
            throw new BadHttpRequestException('Bad http request: '.$this->head);
        }
        $protocolLine = array_shift($headerLines);

        $headers = [];
        foreach ($headerLines as $headerLine) {
            $pair = self::parseHeaderLine($headerLine);
            if (!empty($pair)) {
                $headers[$pair[0]][] = $pair[1];
            }
        }
        $this->createRequest($protocolLine, $headers);
    }

    /**
     * @throws BadHttpRequestException
     */
    private function createRequest(string $protocolLine, array $headers): void
    {
        // HTTP协议头,方法，路径，协议[RFC-2616 5.1]
        $parts = explode(' ', $protocolLine, 3);
        if (count($parts) < 3) {
            throw new BadHttpRequestException('Bad http request '.$protocolLine);
        }
        [$method, $uri, $protocol] = $parts;

        $info = $this->httpServer->getConnectionInfo($this->clientId);
        $server['REQUEST_URI'] = $uri;
        if ($info) {
            $server['REMOTE_ADDR'] = $info->getRemoteIp();
            $server['REMOTE_PORT'] = $info->getRemotePort();
        }
        $server['REQUEST_METHOD'] = $method;
        $server['REQUEST_TIME'] = time();
        $server['SERVER_PROTOCOL'] = $protocol;
        foreach ($headers as $name => $values) {
            $server['HTTP_'.strtoupper(str_replace('-', '_', $name))] = end($values);
        }

        $request = $this->httpServer->getServerRequestFactory()->createServerRequest($method, $this->normalizeUri($uri, $headers), $server);

        foreach ($headers as $name => $values) {
            foreach ($values as $value) {
                $request = $request->withAddedHeader($name, $value);
            }
        }
        $cookie = $request->getHeaderLine(HttpHeaderName::COOKIE);
        if (!empty($cookie)) {
            $request = $request->withCookieParams(self::parseParams($cookie));
        }
        $query = $request->getUri()->getQuery();
        if ('' !== $query) {
            parse_str($query, $queryParams);
            $request = $request->withQueryParams($queryParams);
        }
        $this->request = $request;
    }

    private function normalizeUri(string $uri, array $headers): UriInterface
    {
        $uriObj = $this->httpServer->getUriFactory()->createUri($uri);

        if (isset($headers[HttpHeaderName::X_FORWARDED_HOST])) {
            $hostAndPort = self::parseHostAndPort(end($headers[HttpHeaderName::X_FORWARDED_HOST]));
        } elseif (isset($headers[HttpHeaderName::HOST])) {
            $hostAndPort = self::parseHostAndPort(end($headers[HttpHeaderName::HOST]));
        }
        if (isset($hostAndPort)) {
            $uriObj = $uriObj->withHost($hostAndPort[0]);
            if (isset($hostAndPort[1])) {
                $uriObj = $uriObj->withPort((int) $hostAndPort[1]);
            }
        }
        if (isset($headers[HttpHeaderName::X_FORWARDED_PROTO])
            && 'https' === strtolower(end($headers[HttpHeaderName::X_FORWARDED_PROTO]))) {
            $uriObj = $uriObj->withScheme('https');
        }

        return $uriObj;
    }

    private static function parseHostAndPort(string $host): array
    {
        $port = null;

        if (preg_match('|:(\d+)$|', $host, $matches)) {
            $host = substr($host, 0, -1 * (strlen($matches[1]) + 1));
            $port = (int) $matches[1];
        }

        return [$host, $port];
    }

    /**
     * @throws BadHttpRequestException
     */
    private function checkBody(): void
    {
        if (!isset($this->request)) {
            return;
        }
        $contentLength = (int) $this->request->getHeaderLine('content-length');
        if (0 === $contentLength) {
            $this->completed = true;
        } elseif ($contentLength > 0) {
            $maxSize = $this->httpServer->getSettings()->getInt(ServerSetting::PACKAGE_MAX_LENGTH);
            if ($contentLength > $maxSize) {
                throw new BadHttpRequestException("post data is too large, max size is $maxSize");
            }
            if (strlen($this->body) >= $contentLength) {
                $this->completed = true;
                $this->parseBody();
            }
        }
    }

    private function parseBody(): void
    {
        $this->request = $this->request->withBody($this->httpServer->getStreamFactory()->createStream($this->body));
        $contentType = $this->request->getHeaderLine('Content-Type');
        $pos = stripos($contentType, 'boundary=');
        if (false !== $pos) {
            $this->parseFormData(substr($contentType, $pos + strlen('boundary=')));
        } elseif (false !== stripos($contentType, 'application/x-www-form-urlencoded')) {
            parse_str($this->body, $post);
            $this->request = $this->request->withParsedBody($post);
        }
    }

    private function parseFormData(string $boundary): void
    {
        $boundary = '--'.$boundary;
        $formData = [];
        $files = [];

        $formParts = explode($boundary, $this->body);
        $last = trim(array_pop($formParts));
        if ('--' !== $last) {
            throw new BadHttpRequestException('form data is invalid');
        }
        foreach ($formParts as $paramData) {
            $parts = explode(self::END_OF_HEAD, $paramData, 2);
            $headers = [];
            foreach (explode(self::END_OF_LINE, $parts[0]) as $headLine) {
                $header = self::parseHeaderLine($headLine);
                if (!empty($header)) {
                    $headers[$header[0]] = $header[1];
                }
            }
            if (!isset($headers[HttpHeaderName::CONTENT_DISPOSITION])) {
                continue;
            }
            $params = self::parseParams($headers[HttpHeaderName::CONTENT_DISPOSITION]);
            $name = $params['name'] ?? '';
            //filename字段表示它是一个文件
            if (isset($params['filename'])) {
                $files[$name] = $this->httpServer->getUploadFileFactory()
                    ->createUploadedFile(
                        $this->httpServer->getStreamFactory()->createStream($parts[1]),
                        strlen($parts[1]),
                        UPLOAD_ERR_OK,
                        $params['filename'],
                        $headers[HttpHeaderName::CONTENT_TYPE] ?? null
                    );
            } else {
                //支持checkbox
                if ('[]' === substr($name, -2)) {
                    $formData[substr($name, 0, -2)][] = trim($parts[1]);
                } else {
                    $formData[$name] = trim($parts[1], self::END_OF_LINE);
                }
            }
        }
        if (!empty($formData)) {
            $this->request = $this->request->withParsedBody($formData);
        }
        if (!empty($files)) {
            $this->request = $this->request->withUploadedFiles($files);
        }
    }

    private static function parseHeaderLine(string $headerLine): array
    {
        $headerLine = trim($headerLine);
        if (empty($headerLine)) {
            return [];
        }
        $pair = explode(':', $headerLine, 2);

        return [strtolower(trim($pair[0])), trim($pair[1] ?? '')];
    }

    /**
     * Parse cookie-like param string
     * "key1=value1; key2=value2; ...".
     */
    private static function parseParams(string $paramStr): array
    {
        $params = [];
        $parts = explode(';', $paramStr);
        foreach ($parts as $pairStr) {
            $pair = explode('=', $pairStr, 2);
            $params[trim($pair[0])] = trim($pair[1] ?? '', "\r\n \t\"");
        }

        return $params;
    }
}
