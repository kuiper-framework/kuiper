<?php

namespace kuiper\rpc\client\middleware;

use kuiper\rpc\client\exception\RpcException;
use kuiper\rpc\MiddlewareInterface;
use kuiper\rpc\RequestInterface;
use kuiper\rpc\ResponseInterface;

class JsonRpc implements MiddlewareInterface
{
    /**
     * @var string[]
     */
    private $map;

    /**
     * @var int
     */
    private $id = 1;

    public function __construct(array $map = [])
    {
        $this->map = $map;
    }

    /**
     * {@inheritdoc}
     */
    public function __invoke(RequestInterface $request, ResponseInterface $response, callable $next)
    {
        $serviceName = $request->getClass().'.'.$request->getMethod();

        if (isset($this->map[$serviceName])) {
            $serviceName = $this->map[$serviceName];
        }
        $request->getBody()->write(json_encode([
            'method' => $serviceName,
            'id' => $this->id++,
            'params' => $request->getParameters(),
        ]));
        $response = $next($request, $response);
        $result = json_decode((string) $response->getBody(), true);
        if (isset($result['error'])) {
            $this->handleError($result['error']);
        }

        return $response->withResult($result['result']);
    }

    private function handleError($error)
    {
        if (isset($error['data'])) {
            $data = unserialize(base64_decode($error['data']));
            if ($data !== false) {
                if (is_array($data) && isset($data['class'], $data['message'], $data['code'])) {
                    $this->tryThrowException($data);
                } elseif ($data instanceof \Exception) {
                    throw $data;
                }
            }
        }
        throw new RpcException($error['message'], $error['code']);
    }

    private function tryThrowException($data)
    {
        $className = $data['class'];
        $class = new \ReflectionClass($className);
        $constructor = $class->getConstructor();
        if ($class->isSubClassOf(\Exception::class) && $constructor !== null) {
            $params = $constructor->getParameters();
            if (count($params) > 2) {
                $requiredParams = 0;
                foreach ($params as $param) {
                    if (!$param->isOptional()) {
                        ++$requiredParams;
                    }
                }
                if ($requiredParams <= 2) {
                    throw new $className($data['message'], $data['code']);
                }
            }
        }
    }
}
