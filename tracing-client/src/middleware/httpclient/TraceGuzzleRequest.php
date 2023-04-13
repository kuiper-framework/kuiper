<?php

declare(strict_types=1);

/*
 * This file is part of the Kuiper package.
 *
 * (c) Ye Wenbin <wenbinye@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace kuiper\tracing\middleware\httpclient;

use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Promise as P;
use GuzzleHttp\Promise\PromiseInterface;

use function kuiper\helper\describe_error;

use kuiper\rpc\HasRequestIdInterface;
use kuiper\tracing\Tracer;

use const OpenTracing\Formats\HTTP_HEADERS;

use OpenTracing\NoopSpan;

use const OpenTracing\Tags\PEER_ADDRESS;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

class TraceGuzzleRequest
{
    /**
     * @var callable(RequestInterface, array): PromiseInterface
     */
    private $nextHandler;

    /**
     * @param callable(RequestInterface, array): PromiseInterface $nextHandler next handler to invoke
     */
    public function __construct(callable $nextHandler)
    {
        $this->nextHandler = $nextHandler;
    }

    public function __invoke(RequestInterface $request, array $options): PromiseInterface
    {
        $fn = $this->nextHandler;
        $tracer = Tracer::get();
        $span = $tracer->getActiveSpan();
        if (null === $span || ($span instanceof NoopSpan)) {
            return $fn($request, $options);
        }

        $scope = $tracer->startActiveSpan('call '.$this->getMethodName($request));
        $span = $scope->getSpan();

        $tracer->inject($scope->getSpan()->getContext(), HTTP_HEADERS, $request);
        $update = function (?ResponseInterface $response, $error = null) use ($span, $scope, $request) {
            $span->setTag(PEER_ADDRESS, $request->getUri()->getHost().':'.$request->getUri()->getPort());
            if ($request instanceof HasRequestIdInterface) {
                $span->setTag('peer.request_id', $request->getRequestId());
            }
            $span->setTag('peer.params', $this->getParamsData($request));
            if (isset($error)) {
                if ($error instanceof RequestException) {
                    $response = $error->getResponse();
                }
                $span->setTag('error', describe_error($error));
            }
            if (isset($response)) {
                $span->setTag('peer.return_code', $response->getStatusCode());
                $span->setTag('peer.response_size', $response->getBody()->getSize());
            }
            $scope->close();

            return $response;
        };

        return $fn($request, $options)
            ->then(function (ResponseInterface $response) use ($update) {
                $update($response);

                return $response;
            }, function ($reason) use ($update) {
                $update(null, $reason);

                return P\Create::rejectionFor($reason);
            });
    }

    protected function getMethodName(RequestInterface $request): string
    {
        return $request->getMethod().' '.$request->getUri()->getPath();
    }

    protected function getParamsData(RequestInterface $request): string
    {
        return json_encode($request->getUri()->getQuery(), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
}
