<?php

declare(strict_types=1);

namespace kuiper\tars\server;

use kuiper\rpc\RpcMethodFactoryInterface;
use kuiper\rpc\RpcRequestInterface;
use kuiper\rpc\server\RpcServerRequestFactoryInterface;
use kuiper\tars\exception\ErrorCode;
use kuiper\tars\exception\TarsRequestException;
use kuiper\tars\stream\RequestPacket;
use Psr\Http\Message\RequestInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;

class TarsServerRequestFactory implements RpcServerRequestFactoryInterface, LoggerAwareInterface
{
    use LoggerAwareTrait;
    protected const TAG = '['.__CLASS__.'] ';

    /**
     * @var array
     */
    private $servants;

    /**
     * @var RpcMethodFactoryInterface
     */
    private $rpcMethodFactory;

    /**
     * TarsServerRequestFactory constructor.
     */
    public function __construct(ServerProperties $serverProperties, RpcMethodFactoryInterface $rpcMethodFactory)
    {
        foreach ($serverProperties->getAdapters() as $adapter) {
            $servantName = $adapter->getServant();
            $this->servants[$adapter->getEndpoint()->getPort()][$servantName] = true;
        }
        $this->rpcMethodFactory = $rpcMethodFactory;
    }

    /**
     * {@inheritDoc}
     *
     * @throws TarsRequestException
     */
    public function createRequest(RequestInterface $request): RpcRequestInterface
    {
        $packet = RequestPacket::decode((string) $request->getBody());
        if (!isset($this->servants[$request->getUri()->getPort()][$packet->sServantName])) {
            $this->logger->warning(static::TAG.'cannot find adapter match servant, check config file');
            throw new TarsRequestException($packet, 'Unknown servant '.$packet->sServantName, ErrorCode::SERVER_NO_SERVANT_ERR);
        }
        $rpcMethod = $this->rpcMethodFactory->create($packet->sServantName, $packet->sFuncName, [$packet]);

        return new TarsServerRequest($request, $rpcMethod, $packet);
    }
}
