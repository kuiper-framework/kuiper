<?php

declare(strict_types=1);

namespace kuiper\tars\client;

use kuiper\rpc\InvokingMethod;
use kuiper\rpc\RpcRpcRequest;
use kuiper\tars\core\MethodMetadataInterface;
use kuiper\tars\core\TarsRequestInterface;
use kuiper\tars\stream\RequestPacket;
use kuiper\tars\stream\RequestPacketTrait;
use kuiper\tars\stream\TarsConst;
use kuiper\tars\stream\TarsOutputStream;
use kuiper\tars\type\MapType;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\StreamFactoryInterface;

class TarsRequest extends RpcRpcRequest implements TarsRequestInterface
{
    use RequestPacketTrait;

    /**
     * @var MethodMetadataInterface
     */
    private $metadata;

    /**
     * @var StreamFactoryInterface
     */
    private $streamFactory;

    public function __construct(RequestInterface $request, InvokingMethod $invokingMethod, int $requestId, MethodMetadataInterface $metadata, StreamFactoryInterface $streamFactory)
    {
        parent::__construct($request, $invokingMethod);

        $packet = new RequestPacket();
        $packet->iRequestId = $requestId;
        $packet->sServantName = $metadata->getServantName();
        $packet->sFuncName = $metadata->getMethodName();
        $this->packet = $packet;
        $this->metadata = $metadata;
        $this->streamFactory = $streamFactory;
    }

    public function getBody()
    {
        $packet = $this->packet;
        if (null === $packet->sBuffer) {
            $args = $this->getInvokingMethod()->getArguments();
            if (TarsConst::VERSION === $packet->iVersion) {
                $params = [];
                foreach ($this->metadata->getParameters() as $i => $parameter) {
                    $params[$parameter->getName()] = TarsOutputStream::pack($parameter->getType(), $args[$i] ?? null);
                }
                $packet->sBuffer = TarsOutputStream::pack(MapType::byteArrayMap(), $params);
            } else {
                $os = new TarsOutputStream();
                foreach ($this->metadata->getParameters() as $i => $parameter) {
                    $os->write(0, $args[$i] ?? null, $parameter->getType());
                }
                $packet->sBuffer = (string) $os;
            }
            $this->httpRequest = $this->httpRequest->withBody($this->streamFactory->createStream((string) $packet->encode()));
        }

        return parent::getBody();
    }
}
