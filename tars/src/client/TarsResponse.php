<?php

declare(strict_types=1);

namespace kuiper\tars\client;

use kuiper\rpc\RpcRequestInterface;
use kuiper\rpc\RpcRpcResponse;
use kuiper\tars\stream\ResponsePacket;
use Psr\Http\Message\ResponseInterface;

class TarsResponse extends RpcRpcResponse
{
    /**
     * @var ResponsePacket
     */
    private $packet;

    public function __construct(RpcRequestInterface $request, ResponseInterface $response, ResponsePacket $packet)
    {
        parent::__construct($request, $response);
        $this->packet = $packet;
    }

    public function getResponsePacket(): ResponsePacket
    {
        return $this->packet;
    }
}
