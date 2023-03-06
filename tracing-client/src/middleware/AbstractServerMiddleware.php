<?php

declare(strict_types=1);

namespace kuiper\tracing\middleware;

use kuiper\rpc\HasRequestIdInterface;
use kuiper\tracing\Config;
use kuiper\tracing\Tracer;

use const OpenTracing\Formats\HTTP_HEADERS;
use const OpenTracing\Tags\PEER_SERVICE;

use Psr\Http\Message\RequestInterface;

abstract class AbstractServerMiddleware
{
    public function __construct(private readonly Config $config, private readonly string $format = HTTP_HEADERS)
    {
    }

    abstract protected function getMethodName(RequestInterface $request): string;

    public function handle(RequestInterface $request, callable $next)
    {
        $debugIdHeader = $this->config->getDebugIdHeaderKey();
        if ('' === $request->getHeaderLine($debugIdHeader)
            && !$this->config->isEnabled()) {
            return $next($request);
        }
        $tracer = Tracer::get();
        $root = $tracer->extract($this->format, $request);
        $scope = $tracer->startActiveSpan('serve '.$this->getMethodName($request), array_filter([
            'child_of' => $root,
        ]));
        $span = $scope->getSpan();
        $response = null;
        try {
            $response = $next($request);

            return $response;
        } finally {
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
            $scope->close();
            $tracer->flush();
        }
    }
}
