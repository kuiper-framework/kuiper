<?php

declare(strict_types=1);

namespace kuiper\tars\server\task;

use kuiper\swoole\server\ServerInterface;
use kuiper\swoole\task\ProcessorInterface;
use kuiper\swoole\task\Task;
use kuiper\tars\integration\ServerFServant;
use kuiper\tars\integration\ServerInfo;
use kuiper\tars\server\ServerProperties;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;

class KeepAliveProcessor implements ProcessorInterface, LoggerAwareInterface
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
     * ReportTaskProcessor constructor.
     *
     * @param ServerInterface  $server
     * @param ServerProperties $serverProperties
     * @param ServerFServant   $serverFServant
     */
    public function __construct(ServerInterface $server, ServerProperties $serverProperties, ServerFServant $serverFServant)
    {
        $this->server = $server;
        $this->serverProperties = $serverProperties;
        $this->serverFServant = $serverFServant;
    }

    public function process(Task $task)
    {
        $pid = $this->getServerPid();
        if (null === $pid) {
            return;
        }
        try {
            // TODO 健康检查
            $serverInfo = new ServerInfo();
            $serverInfo->serverName = $this->serverProperties->getServer();
            $serverInfo->application = $this->serverProperties->getApp();
            $serverInfo->pid = $pid;
            foreach ($this->serverProperties->getAdapters() as $adapter) {
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
}
