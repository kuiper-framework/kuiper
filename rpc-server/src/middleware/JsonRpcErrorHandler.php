<?php

namespace kuiper\rpc\server\middleware;

use kuiper\helper\Arrays;
use kuiper\rpc\server\MiddlewareInterface;
use kuiper\rpc\server\RequestInterface;
use kuiper\rpc\server\ResponseInterface;

class JsonRpcErrorHandler implements MiddlewareInterface
{
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

    public function handle($e, RequestInterface $request, ResponseInterface $response)
    {
        if ($e instanceof \Serializable) {
            $data = $e;
        } else {
            $data = [
                'class' => get_class($e),
                'message' => $e->getMessage(),
                'code' => $e->getCode(),
            ];
        }
        $payload = $request->getAttribute('body');
        $response->getBody()->write(json_encode([
            'id' => Arrays::fetch($payload, 'id'),
            'jsonrpc' => Arrays::fetch($payload, 'version', '1.0'),
            'error' => [
                'code' => $e->getCode(),
                'message' => $e->getMessage(),
                'data' => base64_encode(serialize($data)),
            ],
        ]));

        return $response;
    }
}
