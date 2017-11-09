<?php

namespace kuiper\rpc\server\middleware;

use kuiper\helper\Arrays;
use kuiper\rpc\MiddlewareInterface;
use kuiper\rpc\RequestInterface;
use kuiper\rpc\ResponseInterface;
use kuiper\serializer\normalizer\ExceptionNormalizer;
use kuiper\serializer\NormalizerInterface;

class JsonRpcErrorHandler implements MiddlewareInterface
{
    /**
     * @var NormalizerInterface
     */
    private $exceptionNormalizer;

    /**
     * JsonRpcErrorHandler constructor.
     *
     * @param NormalizerInterface $exceptionNormalizer
     */
    public function __construct(NormalizerInterface $exceptionNormalizer = null)
    {
        $this->exceptionNormalizer = $exceptionNormalizer ?: new ExceptionNormalizer();
    }

    /**
     * {@inheritdoc}
     */
    public function __invoke(RequestInterface $request, ResponseInterface $response, callable $next)
    {
        try {
            return $next($request, $response);
        } catch (\Exception $e) {
            return $this->handle($e, $request, $response);
        } catch (\Error $e) {
            return $this->handle($e, $request, $response);
        }
    }

    /**
     * @param \Exception|\Error $exception
     * @param RequestInterface  $request
     * @param ResponseInterface $response
     *
     * @return ResponseInterface
     */
    public function handle($exception, RequestInterface $request, ResponseInterface $response)
    {
        $payload = $request->getAttribute('body');
        $response->getBody()->write(json_encode([
            'id' => Arrays::fetch($payload, 'id'),
            'jsonrpc' => Arrays::fetch($payload, 'version', '1.0'),
            'error' => [
                'code' => $exception->getCode(),
                'message' => $exception->getMessage(),
                'data' => $this->exceptionNormalizer->normalize($exception),
            ],
        ]));

        return $response;
    }
}
