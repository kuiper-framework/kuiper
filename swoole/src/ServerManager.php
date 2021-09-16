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

namespace kuiper\swoole;

use kuiper\swoole\constants\ProcessType;
use kuiper\swoole\exception\ServerStateException;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

class ServerManager implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    protected const TAG = '['.__CLASS__.'] ';

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
        if ($masterPid <= 0) {
            throw new ServerStateException('Server was not started');
        }
        if (!$this->kill($masterPid, SIGTERM)) {
            throw new ServerStateException('Server was failed to stop');
        }
        if (file_exists($this->serverConfig->getMasterPidFile())) {
            @unlink($this->serverConfig->getMasterPidFile());
        }
    }

    /**
     * @throws ServerStateException
     */
    public function reload(): void
    {
        $masterPid = $this->getMasterPid();
        if ($masterPid <= 0) {
            throw new ServerStateException('Server was not started');
        }
        if (!$this->kill($masterPid, SIGUSR1)) {
            throw new ServerStateException('Server was failed to reload');
        }
    }

    public function getMasterPid(): int
    {
        if (file_exists($this->serverConfig->getMasterPidFile())) {
            $pid = (int) file_get_contents($this->serverConfig->getMasterPidFile());
        } else {
            $pidList = $this->getPidListByType(ProcessType::MASTER);
            $pid = ($pidList[0] ?? 0);
        }
        if ($pid > 0 && $this->kill($pid, 0)) {
            return $pid;
        }

        return 0;
    }

    private function getPidListByType(string $processType): array
    {
        $cmd = sprintf("ps -ewo pid,cmd | grep %s | grep %s | grep -v grep | awk '{print $1}'",
            $this->serverConfig->getServerName(), $processType);
        exec($cmd, $pidList);
        $this->logger->debug(static::TAG."get $processType pid list by '$cmd'", ['pid' => $pidList]);

        return array_values(array_map('intval', $pidList));
    }

    private function kill(int $pid, int $signal): bool
    {
        if (function_exists('posix_kill')) {
            return posix_kill($pid, $signal);
        }

        exec("kill -$signal $pid", $output, $ret);

        return 0 === $ret;
    }
}
