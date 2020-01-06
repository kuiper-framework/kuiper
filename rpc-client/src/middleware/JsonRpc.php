<?php

namespace kuiper\rpc\client\middleware;

use kuiper\rpc\client\exception\RpcException;
use kuiper\rpc\client\Request;
use kuiper\rpc\MiddlewareInterface;
use kuiper\rpc\RequestInterface;
use kuiper\rpc\ResponseInterface;
use kuiper\serializer\normalizer\ExceptionNormalizer;
use kuiper\serializer\NormalizerInterface;

class JsonRpc implements MiddlewareInterface
{
    /**
     * @var string[]
     */
    private $map;

    /**
     * @var NormalizerInterface
     */
    private $exceptionNormalizer;

    /**
     * @var int
     */
    private $id = 1;

    public function __construct(array $map = [], NormalizerInterface $exceptionNormalizer = null)
    {
        $this->map = $map;
        $this->exceptionNormalizer = $exceptionNormalizer ?: new ExceptionNormalizer();
    }

    /**
     * {@inheritdoc}
     */
    public function __invoke(RequestInterface $request, ResponseInterface $response, callable $next)
    {
        /** @var Request $request */
        $serviceName = $request->getClass().'.'.$request->getMethod();

        if (isset($this->map[$serviceName])) {
            $serviceName = $this->map[$serviceName];
        }
        $request->getBody()->write(json_encode([
            'method' => $serviceName,
            'id' => $this->id++,
            'params' => $request->getParameters(),
        ]));
        /** @var ResponseInterface $response */
        $response = $next($request, $response);
        $result = json_decode((string) $response->getBody(), true);
        if (isset($result['error'])) {
            $this->handleError($result['error']);
        } else {
            return $response->withResult($result['result']);
        }
    }

    private function handleError($error)
    {
        if (isset($error['data'])) {
            throw $this->exceptionNormalizer->denormalize($error['data'], null);
        } else {
            throw new RpcException($error['message'], $error['code']);
        }
    }
}
