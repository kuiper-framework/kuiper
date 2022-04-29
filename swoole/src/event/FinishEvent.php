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

class FinishEvent extends AbstractServerEvent
{
    private int $taskId;

    private ?string $result = null;

    public function getTaskId(): int
    {
        return $this->taskId;
    }

    public function setTaskId(int $taskId): void
    {
        $this->taskId = $taskId;
    }

    public function setResult(?string $result): void
    {
        $this->result = $result;
    }

    public function getResult(): ?string
    {
        return $this->result;
    }
}
