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
    /**
     * @var int
     */
    private $taskId;

    /**
     * @var int
     */
    private $fromWorkerId;

    /**
     * @var int
     */
    private $taskWorkerId;

    /**
     * @var int
     */
    private $callbackId;

    /**
     * @var mixed
     */
    private $data;

    /**
     * @var mixed
     */
    private $result;

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

    public function setFromWorkerId(int $fromWorkerId): void
    {
        $this->fromWorkerId = $fromWorkerId;
    }

    public function getTaskWorkerId(): int
    {
        return $this->taskWorkerId;
    }

    public function setTaskWorkerId(int $taskWorkerId): void
    {
        $this->taskWorkerId = $taskWorkerId;
    }

    public function getCallbackId(): int
    {
        return $this->callbackId;
    }

    public function setCallbackId(int $callbackId): void
    {
        $this->callbackId = $callbackId;
    }

    /**
     * @return mixed
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * @param mixed $data
     */
    public function setData($data): void
    {
        $this->data = $data;
    }

    /**
     * @return mixed
     */
    public function getResult()
    {
        return $this->result;
    }

    /**
     * @param mixed $result
     */
    public function setResult($result): void
    {
        $this->result = $result;
    }
}
