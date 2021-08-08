<?php

declare(strict_types=1);

namespace kuiper\tars\server\task;

use kuiper\swoole\task\ProcessorInterface;
use kuiper\swoole\task\Task;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;

class ReportTaskProcessor implements ProcessorInterface, LoggerAwareInterface
{
    use LoggerAwareTrait;

    protected const TAG = '['.__CLASS__.'] ';

    public function process(Task $task)
    {
        $this->keepAlive();
    }

    private function keepAlive(): void
    {
    }

    public function sendStat(): void
    {
        try {
            $this->statClient->send();
        } catch (\Exception $e) {
            $this->logger->error(static::TAG.'send stat fail', ['error' => $e->getMessage()]);
        }
    }

    public function sendMonitorInfo(): void
    {
        try {
            $this->monitor->monitor();
        } catch (\Exception $e) {
            $this->logger->error(static::TAG.'send monitor fail', ['error' => $e->getMessage()]);
        }
    }
}
