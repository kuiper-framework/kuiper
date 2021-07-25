<?php

declare(strict_types=1);

namespace kuiper\tars\server;

use kuiper\rpc\RpcMethodInterface;
use kuiper\rpc\RpcRequest;
use kuiper\tars\core\TarsRequestInterface;
use kuiper\tars\stream\RequestPacket;
use kuiper\tars\stream\RequestPacketTrait;
use Psr\Http\Message\RequestInterface;

class TarsServerRequest extends RpcRequest implements TarsRequestInterface
{
    use RequestPacketTrait;

    public function __construct(RequestInterface $request, RpcMethodInterface $rpcMethod, RequestPacket $packet)
    {
        parent::__construct($request, $rpcMethod);
        $this->packet = $packet;
    }
}
