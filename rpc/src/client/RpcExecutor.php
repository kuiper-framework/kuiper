<?php

declare(strict_types=1);

namespace kuiper\rpc\client;

use kuiper\rpc\RpcRequestInterface;

class RpcExecutor implements RpcExecutorInterface
{
    /**
     * @var RpcRequestInterface
     */
    private $request;
    /**
     * @var RpcClientInterfaceRpc
     */
    private $client;

    public function __construct(RpcClientInterfaceRpc $client, RpcRequestInterface $request)
    {
        $this->request = $request;
        $this->client = $client;
    }

    public function mapRequest(callable $mapper): self
    {
        $this->request = $mapper($this->request);

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function execute(): array
    {
        return $this->client->sendRequest($this->request);
    }
}
