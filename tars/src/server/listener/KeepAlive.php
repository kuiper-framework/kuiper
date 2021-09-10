<?php

declare(strict_types=1);

namespace kuiper\tars\server\listener;

use kuiper\event\EventListenerInterface;
use kuiper\swoole\Application;
use kuiper\swoole\event\WorkerStartEvent;
use kuiper\swoole\server\ServerInterface;
use kuiper\tars\integration\ServerFServant;
use kuiper\tars\integration\ServerInfo;
use kuiper\tars\integration\StatFServant;
use kuiper\tars\server\ServerProperties;
use kuiper\tars\server\stat\StatInterface;
use kuiper\tars\type\StructMap;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Swoole\Atomic;
use Swoole\Lock;

class KeepAlive implements EventListenerInterface, LoggerAwareInterface
{
    use LoggerAwareTrait;

    protected const TAG = '['.__CLASS__.'] ';

    /**
     * @var ServerProperties
     */
    private $serverProperties;
    /**
     * @var ServerInterface
     */
    private $server;
    /**
     * @var ServerFServant
     */
    private $serverFServant;
    /**
     * @var StatInterface
     */
    private $stat;

    /**
     * @var StatFServant
     */
    private $statFServant;

    /**
     * @var Lock
     */
    private $lock;

    /**
     * @var int
     */
    private $keepAliveInterval;

    /**
     * @var int
     */
    private $keepAliveTime;

    /**
     * @var int
     */
    private $timer;

    public function __construct(
        ServerProperties $serverProperties,
        ServerInterface $server,
        ServerFServant $serverFServant,
        StatInterface $stat,
        StatFServant $statFServant
    ) {
        $this->serverProperties = $serverProperties;
        $this->server = $server;
        $this->serverFServant = $serverFServant;
        $this->statFServant = $statFServant;
        $this->stat = $stat;
        $config = Application::getInstance()->getConfig();
        $this->keepAliveInterval = (int) ($config->getInt('application.tars.server.keep_alive_interval', 10000) / 1000);
        $this->keepAliveTime = 0;
        $this->lock = new Atomic();
        $this->lock->set($this->keepAliveTime);
    }

    public function __invoke($event): void
    {
        /** @var WorkerStartEvent $event */
        $server = $event->getServer();
        $config = Application::getInstance()->getConfig();
        if ('' === $config->getString('application.tars.server.node')) {
            $this->logger->warning(static::TAG.'keep alive disabled.');

            return;
        }
        $this->keepAlive();
        $this->timer = $server->tick(6000, function () {
            error_log('run task');
        });
//        $server->tick($this->keepAliveInterval * 1000, function (): void {
//            $this->keepAlive();
//        });
//        if ($server->isTaskWorker()) {
//            $server->tick($config->getInt('application.tars.client.report_interval', 60000), function () {
//                $entries = $this->stat->flush();
//                if (count($entries) > 0) {
//                    $map = new StructMap();
//                    foreach ($entries as $entry) {
//                        $map->put($entry->getHead(), $entry->getBody());
//                    }
//                    $this->statFServant->reportMicMsg($map, true);
//                }
//            });
//        }
    }

    public function stop(): void
    {
        swoole_timer_clear_all();
    }

    public function keepAlive(): void
    {
        $value = time();
        $value -= ($value % $this->keepAliveInterval);
        $lockAcquired = $this->lock->cmpset($this->keepAliveTime, $value);
        $this->keepAliveTime = $value;
        if (!$lockAcquired) {
            return;
        }

        $pid = $this->getServerPid();
        if (null === $pid) {
            return;
        }
        try {
            $serverInfo = new ServerInfo();
            $serverInfo->serverName = $this->serverProperties->getServer();
            $serverInfo->application = $this->serverProperties->getApp();
            $serverInfo->pid = $pid;
            foreach ($this->serverProperties->getAdapters() as $adapter) {
                if ('AdminObjAdapter' === $adapter->getAdapterName()) {
                    continue;
                }
                $serverInfo->adapter = $adapter->getAdapterName();
                $this->logger->debug(static::TAG.'send keep alive message', ['server' => $serverInfo]);
                $this->serverFServant->keepAlive($serverInfo);
            }
            $serverInfo->adapter = 'AdminObjAdapter';
            $this->serverFServant->keepAlive($serverInfo);
        } catch (\Exception $e) {
            $this->logger->error(static::TAG.'send server info fail', ['error' => $e->getMessage()]);
        }
    }

    private function getServerPid(): ?int
    {
        if ($this->serverProperties->isExternalMode()) {
            if (!file_exists($this->serverProperties->getServerPidFile())) {
                return null;
            }

            return (int) file_get_contents($this->serverProperties->getServerPidFile());
        }

        return $this->server->getMasterPid();
    }

    public function getSubscribedEvent(): string
    {
        return WorkerStartEvent::class;
    }
}
