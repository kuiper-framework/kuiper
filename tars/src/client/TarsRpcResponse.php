<?php

declare(strict_types=1);

namespace kuiper\tars\client;

use kuiper\rpc\RequestInterface;
use kuiper\rpc\RpcResponse;
use kuiper\tars\stream\ResponsePacket;
use Psr\Http\Message\ResponseInterface;

class TarsRpcResponse extends RpcResponse
{
    /**
     * @var ResponsePacket
     */
    private $packet;

    public function __construct(RequestInterface $request, ResponseInterface $response, ResponsePacket $packet)
    {
        parent::__construct($request, $response);
        $this->packet = $packet;
    }

    public function getResponsePacket(): ResponsePacket
    {
        return $this->packet;
    }
}
