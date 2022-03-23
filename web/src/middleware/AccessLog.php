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

namespace kuiper\web\middleware;

use kuiper\swoole\logger\RequestLogFormatterInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;

class AccessLog implements MiddlewareInterface, LoggerAwareInterface
{
    use LoggerAwareTrait;

    /**
     * @var RequestLogFormatterInterface
     */
    private $formatter;

    /**
     * @var callable|null
     */
    private $requestFilter;

    /**
     * AccessLog constructor.
     *
     * @param RequestLogFormatterInterface $formatter
     * @param callable|null                $requestFilter
     */
    public function __construct(RequestLogFormatterInterface $formatter, ?callable $requestFilter = null)
    {
        $this->formatter = $formatter;
        $this->requestFilter = $requestFilter;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $start = microtime(true);
        try {
            $response = $handler->handle($request);
            if (null === $this->requestFilter || (bool) call_user_func($this->requestFilter, $request, $response)) {
                $this->logger->info(...$this->formatter->format($request, $response, null, $start, microtime(true)));
            }

            return $response;
        } catch (\Exception $error) {
            $format = $this->formatter->format($request, null, $error, $start, microtime(true));
            $this->logger->info(...$format);
            throw $error;
        }
    }
}
