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
     * @var TarsFile[]|null
     */
    private ?array $tarsFiles = null;

    public function __construct(
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly ServerInterface $server,
        private readonly ?string $tarsFilePath)
    {
    }

    public function ping(): string
    {
        return 'pong';
    }

    public function stats(): Stat
    {
        // todo: aggregate all workers stats
        $statArr = $this->server->stats();

        return new Stat(
            startTime: date('c', $statArr['start_time'] ?? time()),
            connections: $statArr['connection_num'] ?? 0,
            acceptCount: $statArr['accept_count'] ?? 0,
            closeCount: $statArr['close_count'] ?? 0,
            requestCount: $statArr['request_count'] ?? 0,
            dispatchCount: $statArr['dispatch_count'] ?? 0,
            pendingTasks: $statArr['tasking_num'] ?? 0,
        );
    }

    public function notify(Notification $notification): void
    {
        $this->logger->info(static::TAG.'receive admin notification', ['message' => $notification]);
        $this->eventDispatcher->dispatch($notification);
    }

    /**
     * @return TarsFile[]
     */
    public function getTarsFiles(): array
    {
        if (!isset($this->tarsFiles)) {
            $this->tarsFiles = $this->listTarsFiles();
        }

        return $this->tarsFiles;
    }

    public function getTarsFileContents(): array
    {
        $ret = [];
        foreach ($this->getTarsFiles() as $tarsFile) {
            $copy = get_object_vars($tarsFile);
            $copy['content'] = file_get_contents($this->tarsFilePath.'/'.$tarsFile->name);
            $ret[] = new TarsFile(...$copy);
        }

        return $ret;
    }

    /**
     * @return TarsFile[]
     */
    private function listTarsFiles(): array
    {
        $list = [];
        if (isset($this->tarsFilePath) && is_dir($this->tarsFilePath)) {
            $tarsFiles = glob($this->tarsFilePath.'/*.tars');
            if (is_array($tarsFiles)) {
                foreach ($tarsFiles as $file) {
                    $list[] = new TarsFile(
                        name: basename($file),
                        md5: md5_file($file)
                    );
                }
            }
        }

        return $list;
    }
}
