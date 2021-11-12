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

namespace kuiper\tars\server;

use kuiper\swoole\server\ServerInterface;
use kuiper\tars\server\servant\AdminServant;
use kuiper\tars\server\servant\Notification;
use kuiper\tars\server\servant\Stat;
use kuiper\tars\server\servant\TarsFile;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;

class AdminServantImpl implements AdminServant, LoggerAwareInterface
{
    use LoggerAwareTrait;

    protected const TAG = '['.__CLASS__.'] ';

    /**
     * @var EventDispatcherInterface
     */
    private $eventDispatcher;

    /**
     * @var ServerInterface
     */
    private $server;

    /**
     * @var string|null
     */
    private $tarsFilePath;

    /**
     * AdminServantImpl constructor.
     *
     * @param EventDispatcherInterface $eventDispatcher
     * @param ServerInterface          $server
     */
    public function __construct(EventDispatcherInterface $eventDispatcher, ServerInterface $server, ?string $tarsFilePath)
    {
        $this->eventDispatcher = $eventDispatcher;
        $this->server = $server;
        $this->tarsFilePath = $tarsFilePath;
    }

    public function ping(): string
    {
        return 'pong';
    }

    public function stats(): Stat
    {
        $stat = new Stat();
        // todo: aggregate all workers stats
        $statArr = $this->server->stats();
        $stat->startTime = date('c', $statArr['start_time'] ?? time());
        $stat->acceptCount = $statArr['accept_count'] ?? 0;
        $stat->closeCount = $statArr['close_count'] ?? 0;
        $stat->connections = $statArr['connection_num'] ?? 0;
        $stat->dispatchCount = $statArr['dispatch_count'] ?? 0;
        $stat->requestCount = $statArr['request_count'] ?? 0;
        $stat->pendingTasks = $statArr['tasking_num'] ?? 0;

        return $stat;
    }

    public function notify(Notification $notification): void
    {
        $this->logger->info(static::TAG.'receive admin notification', ['message' => $notification]);
        $this->eventDispatcher->dispatch($notification);
    }

    public function getTarsFiles(): array
    {
        return $this->listTarsFiles(false);
    }

    public function getTarsFileContents(): array
    {
        return $this->listTarsFiles(true);
    }

    private function listTarsFiles(bool $withContent): array
    {
        $list = [];
        if (isset($this->tarsFilePath) && is_dir($this->tarsFilePath)) {
            $tarsFiles = glob($this->tarsFilePath.'/*.tars');
            if (is_array($tarsFiles)) {
                foreach ($tarsFiles as $file) {
                    $tarsFile = new TarsFile();
                    $tarsFile->name = basename($file);
                    $tarsFile->md5 = md5_file($file);
                    if ($withContent) {
                        $tarsFile->content = file_get_contents($file);
                    }
                    $list[] = $tarsFile;
                }
            }
        }

        return $list;
    }
}
