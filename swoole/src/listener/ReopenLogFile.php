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

namespace kuiper\swoole\listener;

use kuiper\event\EventSubscriberInterface;
use kuiper\logger\LoggerFactoryInterface;
use kuiper\swoole\event\ManagerStartEvent;
use kuiper\swoole\event\StartEvent;
use kuiper\swoole\event\WorkerStartEvent;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;

class ReopenLogFile implements EventSubscriberInterface
{
    /**
     * @var LoggerFactoryInterface
     */
    private $loggerFactory;

    /**
     * @var int[]
     */
    private $fileInodes;

    /**
     * WorkerStartEventListener constructor.
     *
     * @param LoggerFactoryInterface $loggerFactory
     */
    public function __construct(LoggerFactoryInterface $loggerFactory)
    {
        $this->loggerFactory = $loggerFactory;
    }

    /**
     * {@inheritdoc}
     */
    public function __invoke($event): void
    {
        $this->tryClose();
        if ($event instanceof WorkerStartEvent) {
            $event->getServer()->tick(10000, function (): void {
                gc_collect_cycles();
                $this->tryReopen();
            });
        }
    }

    private function forEachHandler(callable $callback): void
    {
        foreach ($this->loggerFactory->getLoggers() as $logger) {
            if (!($logger instanceof Logger)) {
                continue;
            }
            foreach ($logger->getHandlers() as $handler) {
                if (!($handler instanceof StreamHandler)) {
                    continue;
                }
                $callback($handler);
            }
        }
    }

    public function tryClose(): void
    {
        $this->forEachHandler(function (StreamHandler $handler) {
            $handler->close();
        });
    }

    public function tryReopen(): void
    {
        clearstatcache();
        $this->forEachHandler(function (StreamHandler $handler) {
            $filename = $handler->getUrl();
            $fileExists = file_exists($filename);
            if (!isset($this->fileInodes[$filename])) {
                if (!$fileExists) {
                    return;
                }
                $this->fileInodes[$filename] = fileinode($filename);
            }
            if (!$fileExists || $this->fileInodes[$filename] !== fileinode($filename)) {
                $handler->close();
                unset($this->fileInodes[$filename]);
            }
        });
    }

    public function getSubscribedEvents(): array
    {
        return [
            StartEvent::class,
            ManagerStartEvent::class,
            WorkerStartEvent::class,
        ];
    }
}
