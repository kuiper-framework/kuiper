<?php

declare(strict_types=1);

namespace kuiper\metrics\export;

use kuiper\helper\Arrays;
use kuiper\helper\IpUtil;
use kuiper\metrics\metric\MetricId;
use kuiper\metrics\metric\MetricInterface;
use kuiper\metrics\metric\MetricType;
use kuiper\metrics\registry\MetricRegistryInterface;
use kuiper\metrics\registry\NamingStrategyInterface;
use kuiper\web\middleware\RemoteAddress;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class PrometheusExporter implements MiddlewareInterface, ExporterInterface
{
    private array $records = [];

    public function __construct(
        private readonly MetricRegistryInterface $registry,
        private readonly ResponseFactoryInterface $responseFactory,
        private readonly NamingStrategyInterface $namingStrategy,
        private readonly string $endpoint = '/metrics',
        private readonly string|array|null $allowIps = null
    ) {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        error_log('PrometheusExporter::process: '.json_encode([
                $request->getUri()->getPath(), $this->endpoint,
                $this->allowRequest($request),
            ]));
        if ($request->getUri()->getPath() === $this->endpoint
            && $this->allowRequest($request)) {
            $response = $this->responseFactory->createResponse();
            $response->getBody()->write($this->getContent());

            return $response;
        }

        return $handler->handle($request);
    }

    private function allowRequest(ServerRequestInterface $request): bool
    {
        if (null === $this->allowIps) {
            return true;
        }
        $ip = RemoteAddress::get($request);

        return isset($ip) && IpUtil::cidrMatch($ip, $this->allowIps);
    }

    public function getContent(): string
    {
        $this->export();

        $content = implode("\n", Arrays::flatten($this->records));
        error_log('PrometheusExporter::getContent: '.json_encode([$content]));

        return $content;
    }

    public function export(): void
    {
        $this->records = [];
        foreach ($this->registry->metrics() as $metric) {
            $this->render($metric);
        }
    }

    private function render(MetricInterface $metric): void
    {
        $value = $metric->value();
        if (null === $value || (is_array($value) && 0 === count($value))) {
            return;
        }
        $metricId = $metric->getMetricId();
        $name = $this->getMetricName($metricId);
        if (!isset($this->records[$metricId->getName()])) {
            $type = match ($metricId->getType()) {
                MetricType::COUNTER => 'counter',
                MetricType::GAUGE => 'gauge',
                MetricType::TIMER => 'summary',
            };
            $this->records[$metricId->getName()] = [
                "# TYPE {$name} {$type}",
            ];
        }
        $tags = $this->getTags($metricId);
        switch ($metricId->getType()) {
            case MetricType::COUNTER:
            case MetricType::GAUGE:
                $this->records[$metricId->getName()][] = "{$name}{$tags} {$value}";
                break;
            case MetricType::TIMER:
                $this->records[$metricId->getName()][] = "{$name}_count{$tags} ".count($value);
                $this->records[$metricId->getName()][] = "{$name}_sum{$tags} ".array_sum($value);
                break;
        }
    }

    private function getMetricName(MetricId $metricId): string
    {
        return $this->namingStrategy->name($metricId->getName());
    }

    private function getTags(MetricId $metricId): string
    {
        $namingStrategy = $this->namingStrategy;
        $tags = [];
        foreach ($metricId->getTags() as $k => $v) {
            $tags[] = sprintf('%s="%s"', $namingStrategy->tagKey($k), $this->escapeTagValue($namingStrategy->tagValue($v)));
        }

        return count($tags) > 0 ? '{'.implode(',', $tags).'}' : '';
    }

    private function escapeTagValue(string $v): string
    {
        return str_replace(['\\', "\n", '"'], ['\\\\', '\\n', '\\"'], $v);
    }
}
