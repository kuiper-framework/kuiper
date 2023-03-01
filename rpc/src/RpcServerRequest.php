<?php

/** @noinspection PhpMissingReturnTypeInspection */

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

use Psr\Http\Message\ServerRequestInterface;

class RpcServerRequest extends RpcRequest implements RpcServerRequestInterface
{
    public function __construct(
        ServerRequestInterface $httpRequest,
        RpcMethodInterface $rpcMethod,
        array $attributes = [])
    {
        parent::__construct($httpRequest, $rpcMethod, $attributes);
    }

    public function getServerParams()
    {
        /** @var ServerRequestInterface $request */
        $request = $this->getHttpRequest();

        return $request->getServerParams();
    }
}
