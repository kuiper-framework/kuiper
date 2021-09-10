<?php

declare(strict_types=1);

namespace kuiper\tars\server;

use kuiper\rpc\RpcResponse;
use kuiper\tars\core\TarsMethodInterface;
use kuiper\tars\core\TarsRequestInterface;
use kuiper\tars\stream\ResponsePacket;
use kuiper\tars\stream\TarsConst;
use kuiper\tars\stream\TarsOutputStream;
use kuiper\tars\type\MapType;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\StreamInterface;

class TarsServerResponse extends RpcResponse
{
    /**
     * @var ResponsePacket
     */
    private $packet;
    /**
     * @var StreamFactoryInterface
     */
    private $streamFactory;

    public function __construct(TarsRequestInterface $request, ResponseInterface $response, StreamFactoryInterface $streamFactory)
    {
        parent::__construct($request, $response);
        $this->packet = ResponsePacket::createFromRequest($request);
        $this->streamFactory = $streamFactory;
    }

    /**
     * {@inheritDoc}
     */
    public function getBody(): StreamInterface
    {
        $packet = $this->packet;
        if (null === $packet->sBuffer) {
            /** @var TarsMethodInterface $rpcMethod */
            $rpcMethod = $this->getRequest()->getRpcMethod();
            $returnValues = $rpcMethod->getResult();
            $out = [$rpcMethod->getReturnValue()];
            foreach ($rpcMethod->getParameters() as $parameter) {
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
