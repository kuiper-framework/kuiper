<?php

declare(strict_types=1);

namespace kuiper\swoole\server\workers;

class MessageType
{
    public const TASK = 0;
    public const TASK_RESULT = 1;
    public const TASK_FINISH = 2;
    public const TICK = 3;
}
