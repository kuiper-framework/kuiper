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
