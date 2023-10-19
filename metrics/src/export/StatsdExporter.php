<?php

declare(strict_types=1);

namespace kuiper\metrics\export;

use Domnikl\Statsd\Client;
use kuiper\event\attribute\EventListener;
use kuiper\event\EventListenerInterface;
use kuiper\metrics\metric\MetricId;
use kuiper\metrics\metric\MetricType;
use kuiper\metrics\registry\MetricRegistryInterface;
use kuiper\metrics\registry\NamingStrategyInterface;
use kuiper\swoole\event\RequestEndEvent;

#[EventListener]
class StatsdExporter implements EventListenerInterface, ExporterInterface
{
    public function __construct(
        private readonly Client $client,
        private readonly MetricRegistryInterface $registry,
        private readonly NamingStrategyInterface $namingStrategy
    ) {
    }

    public function __invoke(object $event): void
    {
        $this->export();
    }

    public function export(): void
    {
        $this->client->startBatch();

        foreach ($this->registry->metrics() as $metric) {
            $metricId = $metric->getMetricId();
            switch ($metricId->getType()) {
                case MetricType::COUNTER:
                    if (null !== $metric->value()) {
                        $this->client->count($this->getMetricName($metricId), $metric->value(), 1, $this->getTags($metricId));
                    }
                    break;
                case MetricType::GAUGE:
                    if (null !== $metric->value()) {
                        $this->client->gauge($this->getMetricName($metricId), $metric->value(), $this->getTags($metricId));
                    }
                    break;
                case MetricType::TIMER:
                    if (count($metric->value()) > 0) {
                        $name = $this->getMetricName($metricId);
                        $tags = $this->getTags($metricId);
                        foreach ($metric->value() as $value) {
                            $this->client->timing($name, $value, 1, $tags);
                        }
                    }
                    break;
            }
            $metric->clear();
        }

        $this->client->endBatch();
    }

    private function getMetricName(MetricId $metricId): string
    {
        return $this->namingStrategy->name($metricId->getName());
    }

    private function getTags(MetricId $metricId): array
    {
        $tags = [];
        foreach ($metricId->getTags() as $k => $v) {
            $tags[$this->namingStrategy->tagKey($k)] = $this->namingStrategy->tagValue($v);
        }

        return $tags;
    }

    public function getSubscribedEvent(): string
    {
        return RequestEndEvent::class;
    }
}
