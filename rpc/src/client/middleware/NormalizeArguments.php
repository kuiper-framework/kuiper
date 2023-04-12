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

namespace kuiper\rpc\client\middleware;

use kuiper\rpc\MiddlewareInterface;
use kuiper\rpc\RpcRequestHandlerInterface;
use kuiper\rpc\RpcRequestInterface;
use kuiper\rpc\RpcResponseInterface;
use kuiper\serializer\NormalizerInterface;

class NormalizeArguments implements MiddlewareInterface
{
    public function __construct(private readonly NormalizerInterface $normalizer)
    {
    }

    public function process(RpcRequestInterface $request, RpcRequestHandlerInterface $handler): RpcResponseInterface
    {
        $args = $this->normalizer->normalize($request->getRpcMethod()->getArguments());

        return $handler->handle($request->withRpcMethod($request->getRpcMethod()->withArguments($args)));
    }
}
