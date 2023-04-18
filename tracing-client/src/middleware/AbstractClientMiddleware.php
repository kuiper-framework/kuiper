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

namespace kuiper\tracing\middleware;

use function kuiper\helper\describe_error;

use kuiper\rpc\HasRequestIdInterface;
use kuiper\tracing\Tracer;
use OpenTracing\NoopSpan;

use const OpenTracing\Tags\PEER_ADDRESS;

use Psr\Http\Message\RequestInterface;
use Throwable;

abstract class AbstractClientMiddleware
{
    protected string $format;

    abstract protected function getMethodName(RequestInterface $request): string;

    abstract protected function getParamsData(RequestInterface $request): string;

    protected function handle(RequestInterface $request, callable $next): mixed
    {
        $tracer = Tracer::get();
        $span = $tracer->getActiveSpan();
        if (null === $span || ($span instanceof NoopSpan)) {
            return $next($request);
        }

        $scope = $tracer->startActiveSpan('call '.$this->getMethodName($request));
        $span = $scope->getSpan();

        $tracer->inject($scope->getSpan()->getContext(), $this->format, $request);

        $update = function ($response, $error = null) use ($request, $span, $scope) {
            $span->setTag(PEER_ADDRESS, $request->getUri()->getHost().':'.$request->getUri()->getPort());
            if ($request instanceof HasRequestIdInterface) {
                $span->setTag('peer.request_id', $request->getRequestId());
            }
            $span->setTag('peer.params', $this->getParamsData($request));
            if (isset($response)) {
                $span->setTag('peer.return_code', $response->getStatusCode());
                $span->setTag('peer.response_size', $response->getBody()->getSize());
            }
            if (isset($error)) {
                $span->setTag('error', describe_error($error));
            }
            $scope->close();
        };
        try {
            $response = $next($request);
            $update($response);

            return $response;
        } catch (Throwable $e) {
            $update(null, $e);
            throw $e;
        }
    }
}
