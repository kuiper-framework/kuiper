<?php

declare(strict_types=1);

namespace kuiper\rpc\client;

use kuiper\rpc\RequestInterface;

class RpcExecutor implements RpcExecutorInterface
{
    /**
     * @var RequestInterface
     */
    private $request;
    /**
     * @var RpcClientInterface
     */
    private $client;

    public function __construct(RpcClientInterface $client, RequestInterface $request)
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
