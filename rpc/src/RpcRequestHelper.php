<?php

declare(strict_types=1);

namespace kuiper\rpc;

use kuiper\swoole\ConnectionInfo;

class RpcRequestHelper
{
    private const CONNECTION_INFO = '__CONNECTION_INFO';

    public static function addConnectionInfo(RpcRequestInterface $request, ConnectionInfo $connectionInfo): RpcRequestInterface
    {
        return $request->withAttribute(self::CONNECTION_INFO, $connectionInfo);
    }

    public static function getConnectionInfo(RpcRequestInterface $request): ?ConnectionInfo
    {
        return $request->getAttribute(self::CONNECTION_INFO);
    }
}
