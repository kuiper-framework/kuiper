<?php

namespace kuiper\rpc\server\middleware;

use kuiper\helper\Arrays;
use kuiper\rpc\server\MiddlewareInterface;
use kuiper\rpc\server\RequestInterface;
use kuiper\rpc\server\ResponseInterface;

class JsonRpc implements MiddlewareInterface
{
    const METHOD_REGEX = '/^[a-z][a-z0-9\\\\_.]*$/i';
    const ERROR_PARSE = -32700;
    const ERROR_INVALID_REQUEST = -32600;
    const ERROR_INVALID_METHOD = -32601;
    const ERROR_INVALID_PARAMS = -32602;
    const ERROR_INTERNAL = -32603;
    const ERROR_OTHER = -32000;

    /**
     * {@inheritdoc}
     */
    public function __invoke(RequestInterface $request, ResponseInterface $response, callable $next)
    {
        $payload = json_decode((string) $request->getBody(), true);
        if (!is_array($payload)) {
            return $this->fault($response, self::ERROR_PARSE, 'Malformed json: '.json_last_error_msg());
        }
        $requestId = Arrays::fetch($payload, 'id');
        $version = Arrays::fetch($payload, 'version', '1.0');
        if (!in_array($version, ['1.0', '2.0'])) {
            return $this->fault($response, self::ERROR_INVALID_REQUEST, "Json RPC version '{$version}' is invalid", $requestId);
        }
        $method = Arrays::fetch($payload, 'method');
        if (empty($method) || !preg_match(self::METHOD_REGEX, $method)) {
            return $this->fault($response, self::ERROR_INVALID_REQUEST, "Method '{$method} is invalid'", $requestId);
        }
        $parameters = Arrays::fetch($payload, 'params');
        if (!is_array($parameters)) {
            return $this->fault($response, self::ERROR_INVALID_REQUEST, 'Parameters is invalid', $requestId);
        }
        $request = $request
                 ->withAttribute('body', $payload)
                 ->withMethod($method)
                 ->withParameters($parameters);
        $response = $next($request, $response);
        if ($response->getBody()->getSize() == 0) {
            $response->getBody()->write(json_encode([
                'id' => Arrays::fetch($payload, 'id'),
                'jsonrpc' => $version,
                'result' => $response->getResult(),
            ]));
        }

        return $response;
    }

    protected function fault(ResponseInterface $response, $code, $message, $requestId = null)
    {
        $response->getBody()->write(json_encode([
            'id' => $requestId,
            'error' => [
                'code' => $code,
                'message' => $message,
            ],
        ]));

        return $response;
    }
}
