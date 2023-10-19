<?php

declare(strict_types=1);

namespace kuiper\metrics;

use kuiper\metrics\export\PrometheusExporter;
use kuiper\metrics\registry\MetricRegistry;
use kuiper\metrics\registry\NamingStrategy;
use Nyholm\Psr7\Factory\Psr17Factory;
use PHPUnit\Framework\TestCase;

class PrometheusExporterTest extends TestCase
{
    public function testRender()
    {
        $registry = new MetricRegistry();
        $registry->counter('swoole.request')->increment();
        $registry->timer('http.request', ['path' => '/'])->record(120.3);
        $registry->timer('http.request', ['path' => '/user'])->record(40.9);

        $exporter = new PrometheusExporter(
            $registry,
            new Psr17Factory(),
            NamingStrategy::snakeCase(),
        );
        error_log($exporter->getContent());
    }
}
