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

namespace kuiper\rpc;

use kuiper\rpc\exception\InvalidMethodException;

interface RpcMethodFactoryInterface
{
    /**
     * @param object|string $service
     * @param string        $method
     * @param array         $args
     *
     * @return RpcMethodInterface
     *
     * @throws InvalidMethodException if service or method does not exists or annotation not valid
     */
    public function create(object|string $service, string $method, array $args): RpcMethodInterface;
}
