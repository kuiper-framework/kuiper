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

class RpcMethodFactory implements RpcMethodFactoryInterface
{
    /**
     * {@inheritDoc}
     */
    public function create($service, string $method, array $args): RpcMethodInterface
    {
        $serviceName = is_string($service) ? $service : get_class($service);

        return new RpcMethod($service, new ServiceLocator($serviceName), $method, $args);
    }
}
