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

namespace kuiper\tars\client;

use kuiper\rpc\RpcRequest;
use kuiper\tars\core\TarsMethodInterface;
use kuiper\tars\core\TarsRequestInterface;
use kuiper\tars\stream\RequestPacket;
use kuiper\tars\stream\RequestPacketTrait;
use kuiper\tars\stream\TarsConst;
use kuiper\tars\stream\TarsOutputStream;
use kuiper\tars\type\MapType;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\StreamInterface;

class TarsRequest extends RpcRequest implements TarsRequestInterface
{
    use RequestPacketTrait;

    private ?StreamInterface $body = null;

    public function __construct(
        RequestInterface $request,
        TarsMethodInterface $rpcMethod,
        private readonly StreamFactoryInterface $streamFactory,
        int $requestId)
    {
        parent::__construct($request, $rpcMethod);

        $packet = new RequestPacket();
        $packet->iRequestId = $requestId;
        $packet->sServantName = $rpcMethod->getServiceLocator()->getName();
        $packet->sFuncName = $rpcMethod->getMethodName();
        $this->packet = $packet;
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
            /** @var TarsMethodInterface $rpcMethod */
            $rpcMethod = $this->getRpcMethod();
            $args = $rpcMethod->getArguments();
            $packet = $this->packet;
            if (TarsConst::VERSION === $packet->iVersion) {
                $params = [];
                foreach ($rpcMethod->getParameters() as $i => $parameter) {
                    $params[$parameter->getName()] = TarsOutputStream::pack($parameter->getType(), $args[$i] ?? null);
                }
                $packet->sBuffer = TarsOutputStream::pack(MapType::byteArrayMap(), $params);
            } else {
                $os = new TarsOutputStream();
                foreach ($rpcMethod->getParameters() as $i => $parameter) {
                    $os->write(0, $args[$i] ?? null, $parameter->getType());
                }
                $packet->sBuffer = (string) $os;
            }
            $this->body = $this->streamFactory->createStream((string) $packet->encode());
        }

        return $this->body;
    }
}
