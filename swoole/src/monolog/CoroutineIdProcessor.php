<?php

declare(strict_types=1);

namespace kuiper\swoole\monolog;

use kuiper\swoole\coroutine\Coroutine;
use Monolog\Processor\ProcessorInterface;

class CoroutineIdProcessor implements ProcessorInterface
{
    public function __invoke(array $record): array
    {
        $record['extra']['cid'] = Coroutine::isEnabled()
            ? Coroutine::getCoroutineId()
            : getmypid();

        return $record;
    }
}
