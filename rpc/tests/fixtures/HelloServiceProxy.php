<?php

declare(strict_types=1);

namespace kuiper\rpc\fixtures;

use kuiper\rpc\client\RpcExecutorFactoryInterface;

class HelloServiceProxy implements HelloService
{
    /**
     * @var RpcExecutorFactoryInterface
     */
    private $rpcExecutorFactory;

    /**
     * HelloServiceProxy constructor.
     *
     * @param RpcExecutorFactoryInterface $rpcExecutorFactory
     */
    public function __construct(RpcExecutorFactoryInterface $rpcExecutorFactory)
    {
        $this->rpcExecutorFactory = $rpcExecutorFactory;
    }

    public function hello(string $name): string
    {
        [$ret] = $this->rpcExecutorFactory->createExecutor($this, __METHOD__, [$name])
            ->execute();

        return $ret;
    }
}
