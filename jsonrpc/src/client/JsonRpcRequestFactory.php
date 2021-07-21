<?php

declare(strict_types=1);

namespace kuiper\jsonrpc\client;

use kuiper\rpc\client\ProxyGenerator;
use kuiper\rpc\client\RequestFactoryInterface;
use kuiper\rpc\InvokingMethod;
use kuiper\rpc\RequestInterface;

class JsonRpcRequestFactory implements RequestFactoryInterface
{
    /**
     * @var \Psr\Http\Message\RequestFactoryInterface
     */
    private $httpRequestFactory;

    /**
     * @var int
     */
    private $id;

    public function __construct(\Psr\Http\Message\RequestFactoryInterface $httpRequestFactory, ?int $id = null)
    {
        $this->httpRequestFactory = $httpRequestFactory;
        $this->id = $id ?? random_int(0, 1 << 20);
    }

    public function createRequest(object $proxy, string $method, array $args): RequestInterface
    {
        $invokingMethod = new InvokingMethod($proxy, $method, $args);
        $request = $this->httpRequestFactory->createRequest('POST', $this->createUri($invokingMethod));
        $requestId = $this->id++;
        $request->getBody()->write(json_encode([
            'id' => $requestId,
            'jsonrpc' => JsonRpcRequest::JSONRPC_VERSION,
            'method' => $this->createMethod($invokingMethod),
            'params' => $args,
        ]));

        return new JsonRpcRequest($requestId, $request, $invokingMethod);
    }

    protected function createUri(InvokingMethod $method): string
    {
        return '/';
    }

    private function createMethod(InvokingMethod $method): string
    {
        return str_replace('\\', '.', ProxyGenerator::getInterfaceName($method->getTargetClass())).'.'.$method->getMethodName();
    }
}
