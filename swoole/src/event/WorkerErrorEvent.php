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

namespace kuiper\swoole\event;

class WorkerErrorEvent extends AbstractServerEvent
{
    private int $workerId;

    private int $workerPid;

    private int $exitCode;

    private int $signal;

    public function getWorkerId(): int
    {
        return $this->workerId;
    }

    public function setWorkerId(int $workerId): void
    {
        $this->workerId = $workerId;
    }

    public function getWorkerPid(): int
    {
        return $this->workerPid;
    }

    public function setWorkerPid(int $workerPid): void
    {
        $this->workerPid = $workerPid;
    }

    public function getExitCode(): int
    {
        return $this->exitCode;
    }

    public function setExitCode(int $exitCode): void
    {
        $this->exitCode = $exitCode;
    }

    public function getSignal(): int
    {
        return $this->signal;
    }

    public function setSignal(int $signal): void
    {
        $this->signal = $signal;
    }
}
