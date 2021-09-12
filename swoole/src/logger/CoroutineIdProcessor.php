<?php

declare(strict_types=1);

namespace kuiper\swoole\logger;

use kuiper\swoole\coroutine\Coroutine;
use Monolog\Processor\ProcessorInterface;

class CoroutineIdProcessor implements ProcessorInterface
{
    public function __invoke(array $record): array
    {
        $record['extra']['pid'] = getmypid();
        if (Coroutine::isEnabled()) {
            $record['extra']['cid'] = Coroutine::getCoroutineId();
        }

        return $record;
    }
}
