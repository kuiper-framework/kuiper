<?php

declare(strict_types=1);

namespace kuiper\web\middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;

class AccessLog implements MiddlewareInterface, LoggerAwareInterface
{
    use LoggerAwareTrait;

    public const MAIN = '$remote_addr - $remote_user [$time_local] "$request" $status $body_bytes_sent "$http_referer" '
    .'"$http_user_agent" "$http_x_forwarded_for" rt=$request_time';

    /**
     * @var string
     */
    private $format;

    /**
     * 放到 extra 中变量，可选值 query, body, jwt, cookies, headers, header.{name}.
     *
     * @var string[]
     */
    private $extra;

    /**
     * @var int
     */
    private $bodyMaxSize;

    /**
     * AccessLog constructor.
     *
     * @param string[] $extra
     */
    public function __construct(string $format = self::MAIN, array $extra = ['query', 'body'], int $bodyMaxSize = 4096)
    {
        $this->format = $format;
        $this->extra = $extra;
        $this->bodyMaxSize = $bodyMaxSize;
    }

    public function getJwtPayload($tokenHeader)
    {
        if ($tokenHeader && 0 === strpos($tokenHeader, 'Bearer ')) {
            $parts = explode('.', substr($tokenHeader, 7));
            if (isset($parts[1])) {
                return json_decode(base64_decode($parts[1], true), true);
            }
        }

        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $start = microtime(true);
        $response = null;
        try {
            $response = $handler->handle($request);

            return $response;
        } finally {
            $this->writeLog($request, $response, (microtime(true) - $start) * 1000);
        }
    }

    private function writeLog(ServerRequestInterface $request, ?ResponseInterface $response, float $responseTime): void
    {
        $time = sprintf('%.2f', $responseTime);

        $ipList = RemoteAddress::getAll($request);
        $statusCode = isset($response) ? $response->getStatusCode() : 500;
        $responseBodySize = isset($response) ? $response->getBody()->getSize() : 0;
        $message = strtr($this->format, [
            '$remote_addr' => $ipList[0] ?? '-',
            '$remote_user' => $request->getUri()->getUserInfo() ?: '-',
            '$time_local' => strftime('%d/%b/%Y:%H:%M:%S %z'),
            '$request' => strtoupper($request->getMethod())
                .' '.$request->getUri()->getPath()
                .' '.strtoupper($request->getUri()->getScheme()).'/'.$request->getProtocolVersion(),
            '$status' => $statusCode,
            '$body_bytes_sent' => $responseBodySize,
            '$http_referer' => $request->getHeaderLine('Referer'),
            '$http_user_agent' => $request->getHeaderLine('User-Agent'),
            '$http_x_forwarded_for' => implode(',', $ipList),
            '$request_time' => $time,
        ]);
        $extra = [];
        foreach ($this->extra as $name) {
            if ('query' === $name) {
                $extra['query'] = http_build_query($request->getQueryParams());
            } elseif ('body' === $name) {
                $extra['body'] = isset($this->bodyMaxSize) && $request->getBody()->getSize() > $this->bodyMaxSize
                    ? 'body-too-big'
                    : (string) $request->getBody();
            } elseif ('headers' === $name) {
                $extra['headers'] = $request->getHeaders();
            } elseif ('cookies' === $name) {
                $extra['cookies'] = $request->getHeaderLine('cookie');
            } elseif ('jwt' === $name) {
                $extra['jwt'] = $this->getJwtPayload($request->getHeaderLine('Authorization'));
            } elseif (0 === strpos($name, 'header.')) {
                $header = substr($name, 7);
                $extra[$header] = $request->getHeaderLine($header);
            }
        }
        $extra = array_filter($extra);
        if ($statusCode >= 400 && $statusCode < 600) {
            $this->logger->error($message, $extra);
        } else {
            $this->logger->info($message, $extra);
        }
    }
}
