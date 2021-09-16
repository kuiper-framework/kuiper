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

namespace kuiper\tars\server\monitor;

use kuiper\tars\integration\PropertyFServant;
use kuiper\tars\integration\StatPropInfo;
use kuiper\tars\integration\StatPropMsgBody;
use kuiper\tars\integration\StatPropMsgHead;
use kuiper\tars\server\monitor\collector\CollectorInterface;
use kuiper\tars\server\ServerProperties;
use kuiper\tars\type\StructMap;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;

class Monitor implements MonitorInterface, LoggerAwareInterface
{
    use LoggerAwareTrait;

    protected const TAG = '['.__CLASS__.'] ';

    /**
     * @var PropertyFServant
     */
    private $propertyFClient;
    /**
     * @var CollectorInterface[]
     */
    private $collectors;
    /**
     * @var ServerProperties
     */
    private $serverProperties;

    public function __construct(ServerProperties $serverProperties, PropertyFServant $propertyFClient, array $collectors)
    {
        $this->propertyFClient = $propertyFClient;
        $this->collectors = $collectors;
        $this->serverProperties = $serverProperties;
    }

    /**
     * {@inheritDoc}
     */
    public function report(): void
    {
        $msg = new StructMap();
        foreach ($this->collectors as $collector) {
            foreach ($collector->getValues() as $name => $value) {
                $msg->put($this->createHead($name), $this->createBody($collector->getPolicy(), (string) $value));
            }
        }
        $this->logger->debug(static::TAG.'send properties', ['msg' => $msg]);
        $this->propertyFClient->reportPropMsg($msg);
    }

    public function createHead(string $propertyName): StatPropMsgHead
    {
        $propMsgHead = new StatPropMsgHead();
        $propMsgHead->moduleName = $this->serverProperties->getServerName();
        $propMsgHead->ip = $this->serverProperties->getLocalIp();
        $propMsgHead->propertyName = $propertyName;
        $propMsgHead->iPropertyVer = 1;

        return $propMsgHead;
    }

    /**
     * @param string $policy
     * @param string $value
     *
     * @return StatPropMsgBody
     */
    private function createBody(string $policy, string $value): StatPropMsgBody
    {
        $propMsgBody = new StatPropMsgBody();
        $propInfo = new StatPropInfo();
        $propInfo->policy = $policy;
        $propInfo->value = $value;
        $propMsgBody->vInfo = [$propInfo];

        return $propMsgBody;
    }
}
