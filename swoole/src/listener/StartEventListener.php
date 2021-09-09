<?php

declare(strict_types=1);

namespace kuiper\swoole\listener;

use kuiper\event\EventListenerInterface;
use kuiper\swoole\constants\ProcessType;
use kuiper\swoole\event\StartEvent;
use kuiper\swoole\server\SwooleServer;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Swoole\Process;
use Webmozart\Assert\Assert;

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
     * {@inheritdoc}
     */
    public function __invoke($event): void
    {
        Assert::isInstanceOf($event, StartEvent::class);
        /** @var StartEvent $event */
        $this->handleSignal($event);
        $this->changeProcessTitle($event);
        $this->writePidFile($event);
    }

    private function handleSignal(StartEvent $event): void
    {
        // https://github.com/swoole/swoole-src/issues/4186
        // 在 manager 进程中无法使用 Process::signal 方法，需要用 pcntl_signal
        if (function_exists('pcntl_signal') && $event->getServer() instanceof SwooleServer) {
            $masterPid = $event->getServer()->getMasterPid();
            Process::signal(SIGINT, static function () use ($masterPid): void {
                Process::kill($masterPid, SIGTERM);
            });
        }
    }

    private function writePidFile(StartEvent $event): void
    {
        $server = $event->getServer();
        $serverConfig = $server->getServerConfig();
        $pidFile = $serverConfig->getMasterPidFile();
        if (null === $pidFile) {
            return;
        }
        try {
            $dir = dirname($pidFile);
            if (!is_dir($dir) && !mkdir($dir, 0777, true) && !is_dir($dir)) {
                $this->logger->error(static::TAG."Cannot create pid file directory $dir");
                throw new \RuntimeException("Cannot create pid file directory $dir");
            }
            $ret = file_put_contents($pidFile, $server->getMasterPid());
            if (false === $ret) {
                throw new \RuntimeException("Cannot create pid file $pidFile");
            }
            $this->logger->info(static::TAG.'Listening on '.$serverConfig->getPort());
        } catch (\RuntimeException $e) {
            $this->logger->error(static::TAG.'Cannot write pid file: '.$e->getMessage());
            $server->stop();
        }
    }

    /**
     * @param StartEvent $event
     */
    private function changeProcessTitle(StartEvent $event): void
    {
        $serverConfig = $event->getServer()->getServerConfig();
        $port = $serverConfig->getPort();
        @cli_set_process_title(sprintf('%s: %s process %s',
            $serverConfig->getServerName(), ProcessType::MASTER, $port));
    }

    public function getSubscribedEvent(): string
    {
        return StartEvent::class;
    }
}
