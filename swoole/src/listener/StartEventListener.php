<?php

declare(strict_types=1);

namespace kuiper\swoole\listener;

use kuiper\swoole\event\StartEvent;
use kuiper\swoole\SwooleServer;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;

class StartEventListener implements EventListenerInterface, LoggerAwareInterface
{
    use LoggerAwareTrait;

    /**
     * @param StartEvent $event
     */
    public function __invoke($event): void
    {
        $serverConfig = $event->getServer()->getServerConfig();
        @cli_set_process_title(sprintf('%s: %s process', $serverConfig->getServerName(), SwooleServer::MASTER_PROCESS_NAME));

        try {
            $this->writePidFile($serverConfig->getMasterPidFile(), $event->getSwooleServer()->master_pid);
            $this->writePidFile($serverConfig->getManagerPidFile(), $event->getSwooleServer()->manager_pid);
        } catch (\RuntimeException $e) {
            $this->logger->error('Cannot write master and manager pid file: '.$e->getMessage());
            $event->getSwooleServer()->stop();
        }
    }

    private function writePidFile(?string $pidFile, int $pid): void
    {
        if (!$pidFile) {
            return;
        }
        $dir = dirname($pidFile);
        if (!is_dir($dir) && !mkdir($dir, 0777, true) && !is_dir($dir)) {
            $this->logger->error("Cannot create pid file directory $dir");
            throw new \RuntimeException("Cannot create pid file directory $dir");
        }
        $ret = file_put_contents($pidFile, $pid);
        if (false === $ret) {
            throw new \RuntimeException("Cannot create pid file $pidFile");
        }
    }

    public function getSubscribedEvent(): string
    {
        return StartEvent::class;
    }
}
