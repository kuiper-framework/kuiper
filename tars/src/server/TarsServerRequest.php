<?php

declare(strict_types=1);

namespace kuiper\tars\server;

use kuiper\rpc\InvokingMethod;
use kuiper\rpc\RpcRpcRequest;
use kuiper\tars\core\MethodMetadataInterface;
use kuiper\tars\core\TarsRequestInterface;
use kuiper\tars\stream\RequestPacket;
use kuiper\tars\stream\RequestPacketTrait;
use Psr\Http\Message\RequestInterface;

class TarsServerRequest extends RpcRpcRequest implements TarsRequestInterface
{
    use RequestPacketTrait;

    /**
     * @var MethodMetadataInterface
     */
    private $metadata;

    public function __construct(RequestInterface $request, InvokingMethod $invokingMethod, RequestPacket $packet, MethodMetadataInterface $metadata)
    {
        parent::__construct($request, $invokingMethod);
        $this->packet = $packet;
        $this->metadata = $metadata;
    }

    public function getMetadata(): MethodMetadataInterface
    {
        return $this->metadata;
    }
}
