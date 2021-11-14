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

namespace kuiper\swoole\logger;

use kuiper\helper\Arrays;
use kuiper\helper\Text;
use kuiper\reflection\ReflectionType;
use kuiper\web\middleware\RemoteAddress;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class LineRequestLogFormatter implements RequestLogFormatterInterface
{
    public const MAIN = '$remote_addr - $remote_user [$time_local] "$request" $status $body_bytes_sent "$http_referer" '
    .'"$http_user_agent" "$http_x_forwarded_for" rt=$request_time';

    /**
     * @var string|callable
     */
    private $template;

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
     * @var DateFormatterInterface
     */
    private $dateFormatter;

    /**
     * @var CoroutineIdProcessor
     */
    private $pidProcessor;

    /**
     * AccessLog constructor.
     *
     * @param string|callable $template
     * @param string[]        $extra
     * @param mixed           $dateFormat
     */
    public function __construct(
        $template = self::MAIN,
        array $extra = ['query', 'body'],
        int $bodyMaxSize = 4096,
        $dateFormat = '%d/%b/%Y:%H:%M:%S %z')
    {
        if (is_string($template) || is_callable($template)) {
            $this->template = $template;
        } else {
            throw new \InvalidArgumentException('format is invalid');
        }
        $this->extra = $extra;
        $this->bodyMaxSize = $bodyMaxSize;
        $this->pidProcessor = new CoroutineIdProcessor();
        $this->dateFormatter = self::createDateFormatter($dateFormat);
    }

    /**
     * @param mixed $dateFormatter
     *
     * @return DateFormatterInterface
     */
    public static function createDateFormatter($dateFormatter): DateFormatterInterface
    {
        if ($dateFormatter instanceof DateFormatterInterface) {
            return $dateFormatter;
        }
        if (is_string($dateFormatter)) {
            if (substr_count($dateFormatter, '%') >= 2) {
                return new StrftimeDateFormatter($dateFormatter);
            }

            return new DateFormatter($dateFormatter);
        }
        throw new \InvalidArgumentException('Unknown date formatter, must by string or DateFormatterInterface, got '.ReflectionType::describe($dateFormatter));
    }

    /**
     * {@inheritDoc}
     */
    public function format(RequestInterface $request, ?ResponseInterface $response, float $startTime, float $endTime): array
    {
        $messageContext = $this->prepareMessageContext($request, $response, $startTime, $endTime);
        if (is_string($this->template)) {
            return [\strtr($this->template, Arrays::mapKeys($messageContext, function ($key) {
                return '$'.$key;
            })), $messageContext['extra'] ?? []];
        }

        return (array) call_user_func($this->template, $messageContext);
    }

    protected function getJwtPayload(?string $tokenHeader): ?array
    {
        if (Text::isNotEmpty($tokenHeader) && 0 === strpos($tokenHeader, 'Bearer ')) {
            $parts = explode('.', substr($tokenHeader, 7));
            if (isset($parts[1])) {
                return json_decode(base64_decode($parts[1], true), true);
            }
        }

        return null;
    }

    /**
     * Extract message context.
     *
     * @param RequestInterface       $request
     * @param ResponseInterface|null $response
     * @param float                  $startTime
     * @param float                  $endTime
     *
     * @return array
     */
    protected function prepareMessageContext(RequestInterface $request, ?ResponseInterface $response, float $startTime, float $endTime): array
    {
        $time = round(($endTime - $startTime) * 1000, 2);

        $ipList = $this->getIpList($request);
        $statusCode = isset($response) ? $response->getStatusCode() : 500;
        $responseBodySize = isset($response) ? $response->getBody()->getSize() : 0;
        $requestBodySize = $request->getBody()->getSize();
        $messageContext = [
            'remote_addr' => $ipList[0] ?? '-',
            'remote_user' => $request->getUri()->getUserInfo(),
            'time_local' => $this->dateFormatter->format($startTime),
            'request_method' => $request->getMethod(),
            'request_uri' => (string) $request->getUri(),
            'request' => strtoupper($request->getMethod()).' '
                .$request->getUri()->getHost().($request->getUri()->getPort() > 0 ? ':'.$request->getUri()->getPort() : '')
                .$request->getUri()->getPath().' '
                .strtoupper('' !== $request->getUri()->getScheme() ? $request->getUri()->getScheme() : 'tcp').'/'.$request->getProtocolVersion(),
            'status' => $statusCode,
            'body_bytes_sent' => $responseBodySize,
            'body_bytes_recv' => $requestBodySize,
            'http_referer' => $request->getHeaderLine('Referer'),
            'http_user_agent' => $request->getHeaderLine('User-Agent'),
            'http_x_forwarded_for' => implode(',', $ipList),
            'request_time' => $time,
        ];
        $extra = [];
        foreach ($this->extra as $name) {
            if ('query' === $name) {
                $extra['query'] = $this->getQueryString($request);
            } elseif ('body' === $name) {
                $bodySize = $request->getBody()->getSize();
                if ($bodySize > $this->bodyMaxSize) {
                    $extra['body'] = 'body with '.$bodySize.' bytes';
                } else {
                    $body = (string) $request->getBody();
                    if (\mb_check_encoding($body, 'utf-8')) {
                        $extra['body'] = $body;
                    } else {
                        $extra['body'] = 'binary data with '.$bodySize.'bytes';
                    }
                }
            } elseif ('headers' === $name) {
                $extra['headers'] = $request->getHeaders();
            } elseif ('cookies' === $name) {
                $extra['cookies'] = $request->getHeaderLine('cookie');
            } elseif ('jwt' === $name) {
                $extra['jwt'] = $this->getJwtPayload($request->getHeaderLine('Authorization'));
            } elseif (0 === strpos($name, 'header.')) {
                $header = substr($name, 7);
                $extra[$header] = $request->getHeaderLine($header);
            } elseif ('pid' === $name) {
                $extra += call_user_func($this->pidProcessor, [])['extra'];
            }
        }
        $extra = array_filter($extra);
        $messageContext['extra'] = $extra;

        return $messageContext;
    }

    /**
     * @param RequestInterface $request
     *
     * @return array
     */
    protected function getIpList(RequestInterface $request): array
    {
        if ($request instanceof ServerRequestInterface) {
            $ipList = RemoteAddress::getAll($request);
        } else {
            $ipList = [];
        }

        return $ipList;
    }

    /**
     * @param RequestInterface $request
     *
     * @return string
     */
    protected function getQueryString(RequestInterface $request): ?string
    {
        if ($request instanceof ServerRequestInterface) {
            return http_build_query($request->getQueryParams());
        }

        return null;
    }

    /**
     * @return callable|string
     */
    public function getTemplate()
    {
        return $this->template;
    }

    /**
     * @return string[]
     */
    public function getExtra(): array
    {
        return $this->extra;
    }

    /**
     * @return int
     */
    public function getBodyMaxSize(): int
    {
        return $this->bodyMaxSize;
    }

    /**
     * @return DateFormatterInterface
     */
    public function getDateFormatter(): DateFormatterInterface
    {
        return $this->dateFormatter;
    }

    /**
     * @return CoroutineIdProcessor
     */
    public function getPidProcessor(): CoroutineIdProcessor
    {
        return $this->pidProcessor;
    }
}
