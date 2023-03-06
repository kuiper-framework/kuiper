<?php

declare(strict_types=1);

namespace kuiper\tracing\reporter;

use kuiper\tracing\Span;
use Psr\Log\LoggerInterface;

/**
 * LoggingReporter logs all spans.
 */
class LoggingReporter implements ReporterInterface
{
    public function __construct(private readonly LoggerInterface $logger)
    {
    }

    /**
     * {@inheritdoc}
     *
     * @param Span $span
     *
     * @return void
     */
    public function reportSpan(Span $span): void
    {
        $data = [
            'name' => $span->getOperationName(),
            'service' => $span->getTracer()->getServiceName(),
            'trace_id' => $span->getContext()->getContextId(),
            'logs' => array_map(static function ($log) {
                $ts = $log['timestamp'] / 1000;
                $fields = $log['fields'];
                $fields['ts'] = date('H:i:s.', (int) ($ts / 1000)).($ts % 1000);

                return $fields;
            }, $span->getLogs()),
            'tags' => $span->getTags(),
            'timestamp' => $span->getStartTime(),
            'duration' => round(($span->getEndTime() - $span->getStartTime()) / 1000, 2),
        ];
        $this->logger->info(json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
    }

    /**
     * {@inheritdoc}
     *
     * Only implemented to satisfy the sampler interface.
     *
     * @return void
     */
    public function close(): void
    {
        // nothing to do
    }
}
