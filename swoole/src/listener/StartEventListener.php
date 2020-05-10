<?php

declare(strict_types=1);

namespace kuiper\swoole\listener;

use kuiper\event\EventListenerInterface;
use kuiper\swoole\constants\ProcessType;
use kuiper\swoole\event\StartEvent;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

class StartEventListener implements EventListenerInterface, LoggerAwareInterface
{
    use LoggerAwareTrait;

    protected const TAG = '['.__CLASS__.'] ';

    /**
     * StartEventListener constructor.
     */
    public function __construct(?LoggerInterface $logger)
    {
        $this->setLogger($logger ?? new NullLogger());
    }

    /**
     * @param StartEvent $event
     */
    public function __invoke($event): void
    {
        $serverConfig = $event->getServer()->getServerConfig();
        @cli_set_process_title(sprintf('%s: %s process', $serverConfig->getServerName(), ProcessType::MASTER));

        $server = $event->getServer();
        try {
            $this->writePidFile($serverConfig->getMasterPidFile(), $server->getMasterPid());
            $port = $serverConfig->getPort();
            $this->logger->info(static::TAG.'Listening on '.$port);
        } catch (\RuntimeException $e) {
            $this->logger->error(static::TAG.'Cannot write pid file: '.$e->getMessage());
            $server->stop();
        }
    }

    private function writePidFile(?string $pidFile, int $pid): void
    {
        if (!$pidFile) {
            return;
        }
        $dir = dirname($pidFile);
        if (!is_dir($dir) && !mkdir($dir, 0777, true) && !is_dir($dir)) {
            $this->logger->error(static::TAG."Cannot create pid file directory $dir");
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
