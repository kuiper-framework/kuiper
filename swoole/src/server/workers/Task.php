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

namespace kuiper\swoole\server\workers;

class Task
{
    private int $taskId = 0;

    private mixed $result = null;

    public function __construct(
        private int $taskWorkerId,
        private readonly int $fromWorkerId,
        private readonly int $callbackId,
        private readonly mixed $data)
    {
    }

    public function getTaskId(): int
    {
        return $this->taskId;
    }

    public function setTaskId(int $taskId): void
    {
        $this->taskId = $taskId;
    }

    public function getFromWorkerId(): int
    {
        return $this->fromWorkerId;
    }

    public function getTaskWorkerId(): int
    {
        return $this->taskWorkerId;
    }

    public function getCallbackId(): int
    {
        return $this->callbackId;
    }

    public function getData(): mixed
    {
        return $this->data;
    }

    public function getResult(): mixed
    {
        return $this->result;
    }

    public function setResult(mixed $result): void
    {
        $this->result = $result;
    }

    public function setTaskWorkerId(int $taskWorkerId): void
    {
        $this->taskWorkerId = $taskWorkerId;
    }
}
