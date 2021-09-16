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

namespace kuiper\rpc\client;

interface RpcExecutorFactoryInterface
{
    /**
     * @param object $proxy
     * @param string $method
     * @param array  $args
     *
     * @return RpcExecutorInterface
     */
    public function createExecutor(object $proxy, string $method, array $args): RpcExecutorInterface;
}
