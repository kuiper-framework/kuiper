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

use kuiper\rpc\RpcMethodFactoryInterface;
use kuiper\rpc\RpcServerRequestInterface;
use kuiper\rpc\server\RpcServerRequestFactoryInterface;
use kuiper\rpc\server\Service;
use kuiper\tars\exception\ErrorCode;
use kuiper\tars\exception\TarsRequestException;
use kuiper\tars\stream\RequestPacket;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;

class TarsServerRequestFactory implements RpcServerRequestFactoryInterface, LoggerAwareInterface
{
    use LoggerAwareTrait;
    protected const TAG = '['.__CLASS__.'] ';

    /**
     * @param RpcMethodFactoryInterface $rpcMethodFactory
     * @param Service[]                 $services
     */
    public function __construct(
        private readonly RpcMethodFactoryInterface $rpcMethodFactory,
        private readonly array $services)
    {
    }

    /**
     * {@inheritDoc}
     */
    public function createRequest(ServerRequestInterface $request): RpcServerRequestInterface
    {
        $packet = RequestPacket::decode((string) $request->getBody());
        if (!isset($this->services[$packet->sServantName])) {
            $this->logger->warning(static::TAG.'cannot find adapter match servant, check config file');
            throw new TarsRequestException($packet, 'Unknown servant '.$packet->sServantName, ErrorCode::SERVER_NO_SERVANT_ERR);
        }
        if ($this->services[$packet->sServantName]->getServerPort()->getPort() !== $request->getUri()->getPort()) {
            $this->logger->warning(static::TAG.'adapter port not match', [
                'expect' => $this->services[$packet->sServantName]->getServerPort()->getPort(),
                'request' => $request->getUri()->getPort(),
            ]);
            throw new TarsRequestException($packet, 'Unknown servant '.$packet->sServantName, ErrorCode::SERVER_NO_SERVANT_ERR);
        }
        $rpcMethod = $this->rpcMethodFactory->create($packet->sServantName, $packet->sFuncName, [$packet]);

        return new TarsServerRequest($request, $rpcMethod, $packet);
    }
}
