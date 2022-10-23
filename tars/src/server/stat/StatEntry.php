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

namespace kuiper\tars\server\stat;

use kuiper\rpc\RpcResponseInterface;
use kuiper\tars\client\TarsRequest;
use kuiper\tars\client\TarsResponse;
use kuiper\tars\integration\StatMicMsgBody;
use kuiper\tars\integration\StatMicMsgHead;
use kuiper\tars\server\ServerProperties;

final class StatEntry
{
    /**
     * StatEntry constructor.
     */
    private function __construct(
        private readonly int $index,
        private readonly StatMicMsgHead $head,
        private readonly StatMicMsgBody $body)
    {
    }

    public function withBody(StatMicMsgBody $body): self
    {
        return new self($this->index, $this->head, $body);
    }

    private static function removeObj(string $servantName): string
    {
        return substr($servantName, 0, strrpos($servantName, '.'));
    }

    public function getIndex(): int
    {
        return $this->index;
    }

    public function getHead(): StatMicMsgHead
    {
        return $this->head;
    }

    public function getBody(): StatMicMsgBody
    {
        return $this->body;
    }

    public function getUniqueId(): string
    {
        return (string) $this;
    }

    public function __toString(): string
    {
        return implode('|', [
            $this->index,
            $this->head->slaveName,
            $this->head->interfaceName,
            $this->head->slaveIp,
            $this->head->slavePort,
            $this->head->slaveSetName,
            $this->head->slaveSetID,
            $this->head->slaveSetArea,
            $this->head->masterName,
            $this->head->masterIp,
            $this->head->returnValue,
            $this->head->tarsVersion,
        ]);
    }

    public static function fromString(string $key): StatEntry
    {
        $parts = explode('|', $key);
        /** @noinspection PhpNamedArgumentsWithChangedOrderInspection */
        $head = new StatMicMsgHead(
            slaveName: $parts[1],
            interfaceName: $parts[2],
            slaveIp: $parts[3],
            slavePort: (int) $parts[4],
            slaveSetName: $parts[5],
            slaveSetID: $parts[6],
            slaveSetArea: $parts[7],
            masterName: $parts[8],
            masterIp: $parts[9],
            returnValue: (int) $parts[10],
            tarsVersion: $parts[11],
        );

        return new self((int) $parts[0], $head, self::createMsgBody());
    }

    public static function success(int $index, ServerProperties $serverProperties, RpcResponseInterface $response, int $responseTime): StatEntry
    {
        return self::create($index, $serverProperties, $response, $responseTime, ['count' => 1]);
    }

    public static function fail(int $index, ServerProperties $serverProperties, RpcResponseInterface $response, int $responseTime): StatEntry
    {
        return self::create($index, $serverProperties, $response, $responseTime, ['execCount' => 1]);
    }

    public static function timedOut(int $index, ServerProperties $serverProperties, RpcResponseInterface $response, int $responseTime): StatEntry
    {
        return self::create($index, $serverProperties, $response, $responseTime, ['timeoutCount' => 1]);
    }

    private static function create(int $index, ServerProperties $serverProperties, RpcResponseInterface $response, int $responseTime, array $count): StatEntry
    {
        /** @var TarsResponse $response */
        /** @var TarsRequest $request */
        $request = $response->getRequest();
        $head = new StatMicMsgHead(
            masterName: $serverProperties->getServerName(),
            slaveName: self::removeObj($request->getRpcMethod()->getServiceLocator()->getName()),
            interfaceName: $request->getRpcMethod()->getMethodName(),
            masterIp: $serverProperties->getLocalIp(),
            slaveIp: $request->getUri()->getHost(),
            slavePort: $request->getUri()->getPort(),
            returnValue: $response->getResponsePacket()->iRet,
            slaveSetName: '',
            slaveSetArea: '',
            slaveSetID: '',
            tarsVersion: (string) $request->getVersion(),
        );
        $count += [
            'totalRspTime' => $responseTime,
            'minRspTime' => $responseTime,
            'maxRspTime' => $responseTime,
        ];

        return new self($index, $head, self::createMsgBody($count));
    }

    private static function createMsgBody(array $values = []): StatMicMsgBody
    {
        return new StatMicMsgBody(...$values + [
                'count         ' => 0,
                'timeoutCount  ' => 0,
                'execCount     ' => 0,
                'intervalCount ' => [],
                'totalRspTime  ' => 0,
                'maxRspTime    ' => 0,
                'minRspTime    ' => 0,
            ]);
    }
}
