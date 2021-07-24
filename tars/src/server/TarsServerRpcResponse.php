<?php

declare(strict_types=1);

namespace kuiper\tars\server;

use kuiper\rpc\RpcResponse;
use kuiper\tars\core\MethodMetadataInterface;
use kuiper\tars\core\TarsRequestInterface;
use kuiper\tars\stream\ResponsePacket;
use kuiper\tars\stream\TarsConst;
use kuiper\tars\stream\TarsOutputStream;
use kuiper\tars\type\MapType;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;

class TarsServerRpcResponse extends RpcResponse
{
    /**
     * @var ResponsePacket
     */
    private $packet;
    /**
     * @var MethodMetadataInterface
     */
    private $metadata;
    /**
     * @var StreamFactoryInterface
     */
    private $streamFactory;

    public function __construct(TarsRequestInterface $request, ResponseInterface $response, MethodMetadataInterface $metadata, StreamFactoryInterface $streamFactory)
    {
        parent::__construct($request, $response);
        $this->packet = ResponsePacket::createFromRequest($request);
        $this->metadata = $metadata;
        $this->streamFactory = $streamFactory;
    }

    public function getMetadata(): MethodMetadataInterface
    {
        return $this->metadata;
    }

    public function getBody()
    {
        $packet = $this->packet;
        if (null === $packet->sBuffer) {
            $returnValues = $this->getRequest()->getInvokingMethod()->getResult();
            $out = [$this->metadata->getReturnValue()];
            foreach ($this->metadata->getParameters() as $parameter) {
                if ($parameter->isOut()) {
                    $out[] = $parameter;
                }
            }

            if (TarsConst::VERSION === $packet->iVersion) {
                $ret = [];
                foreach ($out as $i => $param) {
                    $ret[$param->getName()] = TarsOutputStream::pack($param->getType(), $returnValues[$i]);
                }
                $packet->sBuffer = TarsOutputStream::pack(MapType::byteArrayMap(), $ret);
            } else {
                $os = new TarsOutputStream();
                foreach ($out as $i => $param) {
                    $os->write($param->getOrder(), $returnValues[$i] ?? null, $param->getType());
                }
                $packet->sBuffer = (string) $os;
            }
            $this->httpResponse = $this->httpResponse->withBody($this->streamFactory->createStream((string) $packet->encode()));
        }

        return parent::getBody();
    }
}
