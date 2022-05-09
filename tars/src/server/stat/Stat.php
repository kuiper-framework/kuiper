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

namespace kuiper\tars\server\stat;

use kuiper\logger\Logger;
use kuiper\rpc\RpcResponseInterface;
use kuiper\tars\server\ClientProperties;
use kuiper\tars\server\ServerProperties;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;

class Stat implements StatInterface, LoggerAwareInterface
{
    use LoggerAwareTrait;

    protected const TAG = '['.__CLASS__.'] ';

    private readonly  int $reportInterval;

    public function __construct(
        private readonly StatStore $store,
        ClientProperties $clientProperties,
        private readonly ServerProperties $serverProperties
    ) {
        $this->reportInterval = $clientProperties->getReportInterval();
        $this->setLogger(Logger::nullLogger());
    }

    public function success(RpcResponseInterface $response, int $responseTime): void
    {
        $timeSlice = $this->getRequestTimeSlice($response);
        $this->store->save(StatEntry::success($timeSlice, $this->serverProperties, $response, $responseTime));
    }

    public function fail(RpcResponseInterface $response, int $responseTime): void
    {
        $this->store->save(StatEntry::fail($this->getRequestTimeSlice($response), $this->serverProperties, $response, $responseTime));
    }

    public function timedOut(RpcResponseInterface $response, int $responseTime): void
    {
        $this->store->save(StatEntry::timedOut($this->getRequestTimeSlice($response), $this->serverProperties, $response, $responseTime));
    }

    public function flush(): array
    {
        $entries = [];
        $currentSlice = $this->getTimeSlice(time());
        foreach ($this->store->getEntries($currentSlice) as $entry) {
            $this->store->delete($entry);
            $entries[] = $entry;
        }

        return $entries;
    }

    private function getTimeSlice(int $time): int
    {
        return (int) ($time / ($this->reportInterval / 1000));
    }

    private function getRequestTimeSlice(RpcResponseInterface $response): int
    {
        return $this->getTimeSlice(time());
    }
}
