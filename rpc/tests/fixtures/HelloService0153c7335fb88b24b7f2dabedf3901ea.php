<?php

declare(strict_types=1);

namespace kuiper\rpc\fixtures;

class HelloService0153c7335fb88b24b7f2dabedf3901ea implements HelloService
{
    private $client = null;

    public function __construct(\kuiper\rpc\client\RpcClientInterfaceRpc $client)
    {
        $this->client = $client;
    }

    /**
     * {@inheritdoc}
     */
    public function hello(string $name): string
    {
        list($ret) = $this->client->sendRequest($this->client->createRequest($this, __FUNCTION__, [$name]));

        return $ret;
    }

    public function createExecutor(string $method, ...$args): \kuiper\rpc\client\RpcExecutorInterface
    {
        return new \kuiper\rpc\client\RpcExecutor($this->client, $this->client->createRequest($this, $method, ...$args));
    }
}
