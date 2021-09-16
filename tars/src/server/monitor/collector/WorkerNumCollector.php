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

namespace kuiper\tars\server\monitor\collector;

use kuiper\tars\server\monitor\MetricPolicy;

class WorkerNumCollector extends AbstractCollector
{
    public function getPolicy(): string
    {
        return MetricPolicy::MIN;
    }

    public function getValues(): array
    {
        exec("ps wwaux | grep {$this->getServerName()} | grep -v grep | wc -l",
            $swooleWorkerNum);
        if (isset($swooleWorkerNum[0])) {
            return [
                'workerNum' => $swooleWorkerNum[0],
            ];
        }

        return [];
    }
}
