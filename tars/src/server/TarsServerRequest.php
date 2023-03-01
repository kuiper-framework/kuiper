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

namespace kuiper\tars\server;

use kuiper\rpc\RpcMethodInterface;
use kuiper\rpc\RpcServerRequest;
use kuiper\tars\core\TarsRequestInterface;
use kuiper\tars\stream\RequestPacket;
use kuiper\tars\stream\RequestPacketTrait;
use Psr\Http\Message\ServerRequestInterface;

class TarsServerRequest extends RpcServerRequest implements TarsRequestInterface
{
    use RequestPacketTrait;

    public function __construct(
        ServerRequestInterface $request,
        RpcMethodInterface $rpcMethod,
        RequestPacket $packet)
    {
        parent::__construct($request, $rpcMethod);
        $this->packet = $packet;
    }
}
