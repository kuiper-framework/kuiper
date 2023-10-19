<?php

declare(strict_types=1);

namespace kuiper\metrics;

use Domnikl\Statsd\Client;
use Domnikl\Statsd\Connection\InMemory;
use kuiper\metrics\export\PrometheusExporter;
use kuiper\metrics\export\StatsdExporter;
use kuiper\metrics\registry\ComposeMetricRegistry;
use kuiper\metrics\registry\MetricRegistry;
use kuiper\metrics\registry\NamingStrategy;
use Nyholm\Psr7\Factory\Psr17Factory;
use PHPUnit\Framework\TestCase;

class ChainedExporterTest extends TestCase
{
    public function testRender()
    {
        $statsdRegistry = new MetricRegistry();
        $prometheusRegistry = new MetricRegistry();
        $registry = new ComposeMetricRegistry([
            $statsdRegistry,
            $prometheusRegistry,
        ]);

        $data = new InMemory();
        $client = new Client($data);
        $statsdExporter = new StatsdExporter($client, $statsdRegistry, NamingStrategy::identity());
        $prometheusExporter = new PrometheusExporter($prometheusRegistry, new Psr17Factory(), NamingStrategy::snakeCase());

        $export = function () use ($registry, $statsdExporter, $prometheusExporter, $data) {
            $registry->counter('swoole.request')->increment();
            $registry->timer('http.request', ['path' => '/'])->record(120.3);
            $registry->timer('http.request', ['path' => '/user'])->record(40.9);

            $statsdExporter->export();
            error_log(var_export($data->getMessages(), true));
            error_log($prometheusExporter->getContent());
        };
        $export();
        $export();
    }
}
