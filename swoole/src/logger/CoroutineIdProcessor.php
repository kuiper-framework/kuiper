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

namespace kuiper\swoole\logger;

use kuiper\swoole\coroutine\Coroutine;
use Monolog\LogRecord;
use Monolog\Processor\ProcessorInterface;

class CoroutineIdProcessor implements ProcessorInterface
{
    public function __invoke(LogRecord $record): LogRecord
    {
        foreach ($this->get() as $name => $val) {
            $record->extra[$name] = $val;
        }

        return $record;
    }

    public function get(): array
    {
        return array_filter([
            'pid' => getmypid(),
            'cid' => Coroutine::getCoroutineId(),
        ]);
    }
}
