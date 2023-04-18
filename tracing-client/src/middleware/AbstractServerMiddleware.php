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
use kuiper\tracing\Config;
use kuiper\tracing\Tracer;

use const OpenTracing\Formats\HTTP_HEADERS;
use const OpenTracing\Tags\PEER_SERVICE;

use Psr\Http\Message\RequestInterface;
use Throwable;

abstract class AbstractServerMiddleware
{
    public function __construct(private readonly Config $config, private readonly string $format = HTTP_HEADERS)
    {
    }

    abstract protected function getMethodName(RequestInterface $request): string;

    protected function handle(RequestInterface $request, callable $next): mixed
    {
        $debugIdHeader = $this->config->getDebugIdHeaderKey();
        if ('' === $request->getHeaderLine($debugIdHeader)
            && !$this->config->isEnabled()) {
            return $next($request);
        }

        return $this->handleRequest($request, $next);
    }

    protected function handleRequest(RequestInterface $request, callable $next): mixed
    {
        $tracer = Tracer::get();
        $root = $tracer->extract($this->format, $request);
        $scope = $tracer->startActiveSpan('serve '.$this->getMethodName($request), array_filter([
            'child_of' => $root,
        ]));
        $update = function ($response, $error = null) use ($scope, $request, $tracer) {
            $span = $scope->getSpan();
            $span->setTag(PEER_SERVICE, $this->getMethodName($request));
            if ($request instanceof HasRequestIdInterface) {
                $span->setTag('peer.request_id', $request->getRequestId());
            }

            if (method_exists($request, 'getServerParams')) {
                $span->setTag('peer.remote_ip', $request->getServerParams()['REMOTE_ADDR'] ?? '-');
            }
            if (isset($response)) {
                $span->setTag('peer.return_code', $response->getStatusCode());
                $span->setTag('peer.response_size', $response->getBody()->getSize());
            }
            if (isset($error)) {
                $span->setTag('error', describe_error($error));
            }
            $scope->close();
            $tracer->flush();
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
