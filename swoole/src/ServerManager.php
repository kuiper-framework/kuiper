<?php

declare(strict_types=1);

namespace kuiper\swoole;

use kuiper\swoole\constants\ProcessType;
use kuiper\swoole\exception\ServerStateException;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

class ServerManager implements LoggerAwareInterface
{
    protected const TAG = '['.__CLASS__.'] ';

    use LoggerAwareTrait;

    /**
     * @var ServerConfig
     */
    private $serverConfig;

    /**
     * ServerManager constructor.
     */
    public function __construct(ServerConfig $serverConfig, ?LoggerInterface $logger)
    {
        $this->serverConfig = $serverConfig;
        $this->setLogger($logger ?? new NullLogger());
    }

    /**
     * @throws ServerStateException
     */
    public function stop(): void
    {
        $masterPid = $this->getMasterPid();
        if (empty($masterPid)) {
            throw new ServerStateException('Server was not started');
        }
        exec("kill -TERM $masterPid", $output, $ret);
        if (0 !== $ret) {
            throw new ServerStateException('Server was failed to stop');
        }
    }

    public function getMasterPid(): int
    {
        if (file_exists($this->serverConfig->getMasterPidFile())) {
            return (int) file_get_contents($this->serverConfig->getMasterPidFile());
        }

        return current($this->getPidListByType(ProcessType::MASTER));
    }

    private function getPidListByType(string $processType): array
    {
        $cmd = sprintf("ps -ewo pid,cmd | grep %s | grep %s | grep -v grep | awk '{print $1}'",
            $this->serverConfig->getServerName(), $processType);
        exec($cmd, $pidList);
        $this->logger->debug(static::TAG."get $processType pid list by '$cmd'", ['pid' => $pidList]);

        return array_map('intval', $pidList);
    }
}
