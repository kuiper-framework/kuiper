<?php

declare(strict_types=1);

namespace kuiper\metrics;

use Domnikl\Statsd\Client;
use Domnikl\Statsd\Connection\InMemory;
use kuiper\metrics\export\StatsdExporter;
use kuiper\metrics\registry\MetricFilter;
use kuiper\metrics\registry\MetricRegistry;
use kuiper\metrics\registry\NamingStrategy;
use PHPUnit\Framework\TestCase;

class StatsdExporterTest extends TestCase
{
    public function testRender()
    {
        $registry = new MetricRegistry([
            MetricFilter::commonTags(['app' => 'test']),
        ]);
        $registry->counter('swoole.request')->increment();
        $registry->timer('http.request', ['path' => '/'])->record(120.3);
        $registry->timer('http.request', ['path' => '/user'])->record(40.9);

        $data = new InMemory();
        $client = new Client($data);
        $exporter = new StatsdExporter($client, $registry, NamingStrategy::identity());
        $exporter->export();
        error_log(var_export($data->getMessages(), true));
    }
}
