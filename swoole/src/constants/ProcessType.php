<?php

declare(strict_types=1);

namespace kuiper\swoole\constants;

use kuiper\helper\Enum;

class ProcessType extends Enum
{
    public const MASTER = 'master';
    public const MANAGER = 'manager';
    public const WORKER = 'worker';
}
