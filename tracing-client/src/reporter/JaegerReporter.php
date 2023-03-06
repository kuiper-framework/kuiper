<?php

declare(strict_types=1);

namespace kuiper\tracing\reporter;

use Exception;
use GuzzleHttp\ClientInterface;
use kuiper\tracing\Span;

class JaegerReporter implements ReporterInterface
{
    /**
     * @var Span[]
     */
    private array $spans = [];

    public function __construct(private readonly ClientInterface $httpClient)
    {
    }

    public function reportSpan(Span $span): void
    {
        $this->spans[] = $span;
    }

    public function close(): void
    {
        $spans = [];
        foreach ($this->spans as $span) {
            $context = $span->getContext();
            $tracer = $span->getTracer();
            $arr = [
                'id' => sprintf('%016x', $context->getSpanId()),
                'traceId' => sprintf('%016x', $context->getTraceId()),
                'name' => $span->getOperationName(),
                'timestamp' => $span->getStartTime(),
                'duration' => $span->getEndTime() - $span->getStartTime(),
                'localEndpoint' => [
                    'serviceName' => $tracer->getServiceName(),
                    'ipv4' => $tracer->getIpAddress(),
                ],
            ];
            if (null !== $context->getParentId()) {
                $arr['parentId'] = sprintf('%016x', $context->getParentId());
            }
            if (count($span->getTags()) > 0) {
                $arr['tags'] = (object) array_map('strval', $span->getTags());
            }
            if (count($span->getLogs()) > 0) {
                $arr['annotations'] = array_map(static function ($log): array {
                    return [
                        'timestamp' => $log['timestamp'],
                        'value' => json_encode($log['fields'], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
                    ];
                }, $span->getLogs());
            }
            $spans[] = $arr;
        }

        try {
            $this->spans = [];
            $this->httpClient->request('POST', '/api/v2/spans', [
                'json' => $spans,
            ]);
        } catch (Exception $e) {
            // ignore error
        }
    }
}
