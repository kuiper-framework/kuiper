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
use kuiper\web\middleware\RemoteAddress;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ServerRequestInterface;

class RequestLogTextFormatter implements RequestLogFormatterInterface
{
    public const MAIN = '$remote_addr - $remote_user [$time_local] "$request" $status $body_bytes_sent "$http_referer" '
    .'"$http_user_agent" "$http_x_forwarded_for" rt=$request_time';

    /**
     * @var string|callable
     */
    private $template;

    /**
     * @var DateFormatterInterface
     */
    private readonly DateFormatterInterface $dateFormatter;

    /**
     * @var CoroutineIdProcessor
     */
    private readonly CoroutineIdProcessor $pidProcessor;

    /**
     * AccessLog constructor.
     *
     * @param string|callable               $template
     * @param string[]                      $extra      放到 extra 中变量，可选值 query, body, jwt, cookies, headers, header.{name}.
     * @param string|DateFormatterInterface $dateFormat
     */
    public function __construct(
        string|callable $template = self::MAIN,
        private readonly array $extra = ['query', 'body'],
        private readonly int $bodyMaxSize = 4096,
        string|DateFormatterInterface $dateFormat = 'd/M/Y:H:i:s O')
    {
        $this->template = $template;
        $this->pidProcessor = new CoroutineIdProcessor();
        $this->dateFormatter = self::createDateFormatter($dateFormat);
    }

    public static function createDateFormatter(string|DateFormatterInterface $dateFormatter): DateFormatterInterface
    {
        if ($dateFormatter instanceof DateFormatterInterface) {
            return $dateFormatter;
        }

        return new DateFormatter($dateFormatter);
    }

    /**
     * {@inheritDoc}
     */
    public function format(LogContext $context): array
    {
        $messageContext = $this->prepareMessageContext($context);
        if (is_string($this->template)) {
            return [\strtr($this->template, Arrays::mapKeys($messageContext, static function ($key) {
                return '$'.$key;
            })), $messageContext['extra'] ?? []];
        }

        return (array) call_user_func($this->template, $messageContext);
    }

    protected function getJwtPayload(?string $tokenHeader): ?array
    {
        if (Text::isNotEmpty($tokenHeader) && str_starts_with($tokenHeader, 'Bearer ')) {
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
     * @param LogContext $context
     *
     * @return array
     */
    protected function prepareMessageContext(LogContext $context): array
    {
        $time = round($context->getRequestTime() * 1000, 2);

        $request = $context->getRequest();
        $ipList = $this->getIpList($request);
        if ($context->hasResponse()) {
            $statusCode = $context->getResponse()->getStatusCode();
        } else {
            $error = $context->getError();
            if (isset($error) && is_int($error->getCode()) && $error->getCode() > 0) {
                $statusCode = $error->getCode();
            } else {
                $statusCode = 500;
            }
        }
        $responseBodySize = $context->hasResponse() ? $context->getResponse()->getBody()->getSize() : 0;
        $requestBodySize = $request->getBody()->getSize();
        $messageContext = [
            'remote_addr' => $ipList[0] ?? '-',
            'remote_user' => $request->getUri()->getUserInfo(),
            'time_local' => $this->dateFormatter->format($context->getStartTime()),
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
            } elseif (str_starts_with($name, 'header.')) {
                $header = substr($name, 7);
                $extra[$header] = $request->getHeaderLine($header);
            } elseif ('pid' === $name) {
                $extra += $this->pidProcessor->get();
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
    public function getTemplate(): callable|string
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
