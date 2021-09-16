<?php

/*
 * This file is part of the Kuiper package.
 *
 * (c) Ye Wenbin <wenbinye@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

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
