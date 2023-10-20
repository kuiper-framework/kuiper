<?php

declare(strict_types=1);

namespace kuiper\metrics;

use DI\Attribute\Inject;

use function DI\value;

use Domnikl\Statsd\Client;
use Domnikl\Statsd\Connection\TcpSocket;
use Domnikl\Statsd\Connection\UdpSocket;
use kuiper\di\attribute\AllConditions;
use kuiper\di\attribute\Bean;
use kuiper\di\attribute\ConditionalOnBean;
use kuiper\di\attribute\ConditionalOnClass;
use kuiper\di\attribute\ConditionalOnProperty;
use kuiper\di\attribute\Configuration;
use kuiper\di\Bootstrap;
use kuiper\di\ContainerBuilderAwareTrait;
use kuiper\di\DefinitionConfiguration;

use function kuiper\helper\env;

use kuiper\logger\LoggerFactoryInterface;
use kuiper\metrics\export\PrometheusExporter;
use kuiper\metrics\export\StatsdExporter;
use kuiper\metrics\export\StatsdServerExporter;
use kuiper\metrics\registry\ComposeMetricRegistry;
use kuiper\metrics\registry\MetricRegistry;
use kuiper\metrics\registry\MetricRegistryInterface;
use kuiper\metrics\registry\NamingStrategy;
use kuiper\swoole\Application;
use kuiper\swoole\attribute\BootstrapConfiguration;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseFactoryInterface;

#[Configuration]
#[BootstrapConfiguration]
class MetricsConfiguration implements DefinitionConfiguration, Bootstrap
{
    use ContainerBuilderAwareTrait;

    public function getDefinitions(): array
    {
        $config = Application::getInstance()->getConfig();
        $config->mergeIfNotExists([
            'application' => [
                'metrics' => [
                    'statsd' => [
                        'enabled' => 'true' === env('METRICS_STATSD_ENABLED'),
                        'host' => env('METRIC_STATSD_HOST'),
                        'port' => (int) env('METRIC_STATSD_PORT', '8125'),
                        'protocol' => env('METRIC_STATSD_PROTOCOL'),
                    ],
                    'prometheus' => [
                        'enabled' => 'true' === env('METRICS_PROMETHEUS_ENABLED'),
                        'endpoint' => env('METRICS_PROMETHEUS_ENDPOINT', '/metrics'),
                        'allow_ips' => env('METRICS_PROMETHEUS_ALLOW_IPS'),
                    ],
                ],
            ],
        ]);

        if ($config->getBool('application.metrics.prometheus.enabled')
            && $config->get('application.metrics.prometheus.endpoint')) {
            $config->appendTo('application.web.middleware', PrometheusExporter::class);
        }

        return [
            'statsdMetricFilters' => value([]),
            'prometheusMetricFilters' => value([]),
        ];
    }

    public function boot(ContainerInterface $container): void
    {
        Metrics::setDefaultRegistry($container->get(MetricRegistryInterface::class));
    }

    #[Bean('statsdMetricRegistry')]
    #[AllConditions(
        new ConditionalOnProperty('application.metrics.statsd.enabled', hasValue: true),
        new ConditionalOnBean(Client::class)
    )]
    public function statsdMetricRegistry(#[Inject('statsdMetricFilters')] array $metricFilters): MetricRegistryInterface
    {
        return new MetricRegistry($metricFilters);
    }

    #[Bean]
    #[ConditionalOnBean('statsdMetricRegistry')]
    public function statsdExporter(Client $client, #[Inject('statsdMetricRegistry')] MetricRegistryInterface $registry): StatsdExporter
    {
        return new StatsdExporter($client, $registry, NamingStrategy::identity());
    }

    #[Bean]
    #[AllConditions(
        new ConditionalOnProperty('application.metrics.statsd.enabled', hasValue: true),
        new ConditionalOnClass(Client::class)
    )]
    public function statsdClient(#[Inject('application.metrics.statsd')] array $options): Client
    {
        $protocol = $options['protocol'] ?? 'udp';
        $connectionClass = 'udp' === $protocol ? UdpSocket::class : TcpSocket::class;
        $connection = new $connectionClass($options['host'] ?? 'localhost', (int) ($options['port'] ?? 8125));

        return new Client($connection, $options['namespace'] ?? '', (float) ($options['sampleRate'] ?? 1.0));
    }

    #[Bean]
    #[ConditionalOnBean('statsdMetricRegistry')]
    public function statsdServerExporter(StatsdExporter $statsdExporter, LoggerFactoryInterface $loggerFactory): StatsdServerExporter
    {
        $statsdServerExporter = new StatsdServerExporter($statsdExporter);
        $statsdServerExporter->setLogger($loggerFactory->create(StatsdServerExporter::class));

        return $statsdServerExporter;
    }

    #[Bean('prometheusMetricRegistry')]
    #[ConditionalOnProperty('application.metrics.prometheus.enabled', hasValue: true)]
    public function prometheusMetricRegistry(#[Inject('prometheusMetricFilters')] array $metricFilters): MetricRegistryInterface
    {
        return new MetricRegistry($metricFilters);
    }

    #[Bean]
    #[ConditionalOnBean('prometheusMetricRegistry')]
    public function prometheusExporter(
        #[Inject('prometheusMetricRegistry')] MetricRegistryInterface $registry,
        ResponseFactoryInterface $responseFactory,
        #[Inject('application.metrics.prometheus')] array $options
    ): PrometheusExporter {
        return new PrometheusExporter($registry, $responseFactory, NamingStrategy::identity(), $options['endpoint'] ?? '/metrics', $options['allow_ips'] ?? null);
    }

    #[Bean]
    public function metricRegistry(ContainerInterface $container, #[Inject('application.metrics')] array $options): MetricRegistryInterface
    {
        $registryList = [];
        foreach ($options as $export => $option) {
            if (isset($option['enabled']) && $option['enabled']
                && $container->has($export.'MetricRegistry')) {
                $registryList[] = $container->get($export.'MetricRegistry');
            }
        }
        if (count($registryList) > 1) {
            return new ComposeMetricRegistry($registryList);
        } elseif (1 == count($registryList)) {
            return $registryList[0];
        } else {
            return new MetricRegistry();
        }
    }
}
