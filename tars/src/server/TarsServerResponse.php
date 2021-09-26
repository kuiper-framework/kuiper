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

use kuiper\rpc\RpcResponse;
use kuiper\tars\core\TarsMethodInterface;
use kuiper\tars\core\TarsRequestInterface;
use kuiper\tars\core\TarsResponseInterface;
use kuiper\tars\stream\ResponsePacket;
use kuiper\tars\stream\TarsConst;
use kuiper\tars\stream\TarsOutputStream;
use kuiper\tars\type\MapType;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\StreamInterface;

class TarsServerResponse extends RpcResponse implements TarsResponseInterface
{
    /**
     * @var ResponsePacket
     */
    private $packet;
    /**
     * @var StreamFactoryInterface
     */
    private $streamFactory;

    /**
     * @var StreamInterface
     */
    private $body;

    public function __construct(TarsRequestInterface $request, ResponseInterface $response, StreamFactoryInterface $streamFactory)
    {
        parent::__construct($request, $response);
        $this->packet = ResponsePacket::createFromRequest($request);
        $this->streamFactory = $streamFactory;
    }

    public function getResponsePacket(): ResponsePacket
    {
        return $this->packet;
    }

    public function withBody(StreamInterface $body)
    {
        $copy = clone $this;
        $copy->body = $body;

        return $copy;
    }

    /**
     * {@inheritDoc}
     */
    public function getBody(): StreamInterface
    {
        if (null === $this->body) {
            $packet = $this->packet;
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
            $this->body = $this->streamFactory->createStream((string) $packet->encode());
        }

        return $this->body;
    }
}
