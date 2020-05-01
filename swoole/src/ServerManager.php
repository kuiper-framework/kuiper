<?php

declare(strict_types=1);

namespace kuiper\swoole;

use kuiper\swoole\constants\ProcessType;
use kuiper\swoole\exception\ServerStateException;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;

class ServerManager implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    /**
     * @var ServerConfig
     */
    private $serverConfig;

    /**
     * ServerManager constructor.
     */
    public function __construct(ServerConfig $serverConfig)
    {
        $this->serverConfig = $serverConfig;
    }

    /**
     * @throws ServerStateException
     */
    public function stop(): void
    {
        $pidList = $this->getAllPidList();
        if (empty($pidList)) {
            throw new ServerStateException('Server was not started');
        }
        exec('kill -9 '.implode(' ', $pidList), $output, $ret);
        if (0 !== $ret) {
            throw new ServerStateException('Server was failed to stop');
        }
    }

    public function getAllPidList()
    {
        $pidList[] = $this->getMasterPid();
        $pidList[] = $this->getManagerPid();
        $pidList = array_merge($pidList, $this->getWorkerPidList());

        return array_filter($pidList);
    }

    public function getMasterPid()
    {
        return current($this->getPidListByType(ProcessType::MASTER));
    }

    public function getManagerPid()
    {
        return current($this->getPidListByType(ProcessType::MANAGER));
    }

    public function getWorkerPidList(): array
    {
        return $this->getPidListByType(ProcessType::WORKER);
    }

    private function getPidListByType(string $processType): array
    {
        $cmd = sprintf("ps -ewo pid,cmd | grep %s | grep %s | grep -v grep | awk '{print $1}'",
            $this->serverConfig->getServerName(), $processType);
        exec($cmd, $pidList);
        $this->logger->debug("[SwooleServer] get $processType pid list by '$cmd'", ['pid' => $pidList]);

        return array_map('intval', $pidList);
    }
}
