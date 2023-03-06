<?php

/*
 * This file is part of the Kuiper package.
 *
 * (c) Ye Wenbin <wenbinye@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/** @noinspection PhpUnusedPrivateMethodInspection */

declare(strict_types=1);

namespace kuiper\tracing;

use Exception;
use InvalidArgumentException;
use kuiper\http\client\HttpClientFactoryInterface;
use kuiper\tracing\reporter\CompositeReporter;
use kuiper\tracing\reporter\JaegerReporter;
use kuiper\tracing\reporter\LoggingReporter;
use kuiper\tracing\reporter\NullReporter;
use kuiper\tracing\reporter\ReporterInterface;
use kuiper\tracing\sampler\ConstSampler;
use kuiper\tracing\sampler\ProbabilisticSampler;
use kuiper\tracing\sampler\RateLimitingSampler;
use kuiper\tracing\sampler\SamplerInterface;
use OpenTracing\GlobalTracer;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;

class Config implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    private string $serviceName;

    private bool $initialized = false;

    public function __construct(
        private array $config,
        string $serviceName = null,
        private readonly ?CacheItemPoolInterface $cache = null,
        private readonly ?HttpClientFactoryInterface $httpClientFactory = null
    ) {
        $this->setConfigFromEnv();

        $this->serviceName = $this->config['service_name'] ?? $serviceName ?? '';
        if ('' === $this->serviceName) {
            throw new InvalidArgumentException('service_name required in the config or param.');
        }
    }

    /**
     * @return Tracer|null
     *
     * @throws Exception
     */
    public function initializeTracer(): ?\OpenTracing\Tracer
    {
        if ($this->initialized) {
            $this->logger->warning('TRACING tracer already initialized, skipping');

            return null;
        }

        $reporter = $this->getReporter();
        $sampler = $this->getSampler();

        $tracer = $this->createTracer($reporter, $sampler);

        $this->initializeGlobalTracer($tracer);

        return $tracer;
    }

    /**
     * @param ReporterInterface $reporter
     * @param SamplerInterface  $sampler
     *
     * @return Tracer
     */
    public function createTracer(?ReporterInterface $reporter = null, ?SamplerInterface $sampler = null): Tracer
    {
        return new Tracer(
            $this->serviceName,
            $reporter ?? $this->getReporter(),
            $sampler ?? $this->getSampler(),
            $this->shouldUseOneSpanPerRpc(),
            $this->logger,
            null,
            $this->getTraceIdHeader(),
            $this->getBaggageHeaderPrefix(),
            $this->getDebugIdHeaderKey(),
            $this->getConfiguredTags(),
            $this->getIpAddress()
        );
    }

    /**
     * @return string
     */
    public function getServiceName(): string
    {
        return $this->serviceName;
    }

    /**
     * @return bool
     */
    public function isEnabled(): bool
    {
        return (bool) ($this->config['enabled'] ?? false);
    }

    /**
     * @param Tracer $tracer
     */
    private function initializeGlobalTracer(Tracer $tracer): void
    {
        GlobalTracer::set($tracer);
        $this->logger->debug('OpenTracing\GlobalTracer initialized to '.$tracer->getServiceName());
    }

    /**
     * @return bool
     */
    private function getLogging(): bool
    {
        return (bool) ($this->config['logging'] ?? false);
    }

    /**
     * @return ReporterInterface
     */
    public function getReporter(): ReporterInterface
    {
        $reporters = [];
        if (isset($this->config['reporting_url'])) {
            if (null !== $this->httpClientFactory) {
                $reporters[] = new JaegerReporter($this->httpClientFactory->create([
                    'base_uri' => $this->config['reporting_url'],
                    'connect_timeout' => $this->config['connect_timeout'] ?? 1,
                    'timeout' => $this->config['timeout'] ?? 1,
                    'http_errors' => false,
                ]));
            } else {
                throw new InvalidArgumentException('You cannot send to jaeger without httpClientFactory component');
            }
        }

        if ($this->getLogging()) {
            $reporters[] = new LoggingReporter($this->logger);
        }
        if (count($reporters) > 1) {
            return new CompositeReporter(...$reporters);
        } elseif (1 === count($reporters)) {
            return $reporters[0];
        } else {
            return new NullReporter();
        }
    }

    /**
     * @return SamplerInterface
     */
    public function getSampler(): SamplerInterface
    {
        $samplerConfig = $this->config['sampler'] ?? [];
        $samplerType = $samplerConfig['type'] ?? null;
        $samplerParam = $samplerConfig['param'] ?? null;

        if (null === $samplerType || Constants::SAMPLER_TYPE_REMOTE === $samplerType) {
            // todo: implement remote sampling
            return new ProbabilisticSampler((float) $samplerParam);
        }

        if (Constants::SAMPLER_TYPE_CONST === $samplerType) {
            return new ConstSampler((bool) $samplerParam);
        }

        if (Constants::SAMPLER_TYPE_PROBABILISTIC === $samplerType) {
            return new ProbabilisticSampler((float) $samplerParam);
        }

        if (Constants::SAMPLER_TYPE_RATE_LIMITING === $samplerType) {
            if (null === $this->cache) {
                throw new InvalidArgumentException('You cannot use RateLimitingSampler without cache component');
            }
            $cacheConfig = $samplerConfig['cache'] ?? [];

            return new RateLimitingSampler(
                $samplerParam ?? 0,
                new RateLimiter(
                    $this->cache,
                    $cacheConfig['currentBalanceKey'] ?? 'rate.currentBalance',
                    $cacheConfig['lastTickKey'] ?? 'rate.lastTick'
                )
            );
        }
        throw new InvalidArgumentException('Unknown sampler type '.$samplerType);
    }

    /**
     * The UDP max buffer length.
     *
     * @return int
     */
    private function getMaxBufferLength(): int
    {
        return (int) ($this->config['max_buffer_length'] ?? 64000);
    }

    /**
     * @return string
     */
    private function getLocalAgentReportingHost(): string
    {
        return $this->getLocalAgentGroup()['reporting_host'] ?? Constants::DEFAULT_REPORTING_HOST;
    }

    /**
     * @return int
     */
    private function getLocalAgentReportingPort(): int
    {
        return (int) ($this->getLocalAgentGroup()['reporting_port'] ?? Constants::DEFAULT_REPORTING_PORT);
    }

    /**
     * @return array
     */
    private function getLocalAgentGroup(): array
    {
        return $this->config['local_agent'] ?? [];
    }

    /**
     * @return string
     */
    private function getTraceIdHeader(): string
    {
        return $this->config['trace_id_header'] ?? Constants::TRACE_ID_HEADER;
    }

    /**
     * @return string
     */
    private function getBaggageHeaderPrefix(): string
    {
        return $this->config['baggage_header_prefix'] ?? Constants::BAGGAGE_HEADER_PREFIX;
    }

    /**
     * @return string
     */
    public function getDebugIdHeaderKey(): string
    {
        return $this->config['debug_id_header_key'] ?? Constants::DEBUG_ID_HEADER_KEY;
    }

    /**
     * Get a list of user-defined tags to be added to each span created by the tracer initialized by this config.
     *
     * @return string[]
     */
    private function getConfiguredTags(): array
    {
        return $this->config['tags'] ?? [];
    }

    private function getIpAddress(): ?string
    {
        return $this->config['ip'] ?? null;
    }

    /**
     * Whether to follow the Zipkin model of using one span per RPC,
     * as opposed to the model of using separate spans on the RPC client and server.
     * Defaults to true.
     *
     * @return bool
     */
    private function shouldUseOneSpanPerRpc(): bool
    {
        return $this->config['one_span_per_rpc'] ?? true;
    }

    /**
     * Sets values from env vars into config props, unless ones has been already set.
     */
    private function setConfigFromEnv(): void
    {
        if (isset($_ENV['TRACING_REPORTING_URL'])) {
            $this->config['reporting_url'] = filter_var($_ENV['TRACING_REPORTING_URL'], FILTER_VALIDATE_URL);
        }
        if (isset($_ENV['TRACING_ENABLED'])) {
            $this->config['enabled'] = filter_var($_ENV['TRACING_ENABLED'], FILTER_VALIDATE_BOOLEAN);
        }
        // general
        if (isset($_ENV['TRACING_SERVICE_NAME']) && !isset($this->config['service_name'])) {
            $this->config['service_name'] = $_ENV['TRACING_SERVICE_NAME'];
        }

        if (isset($_ENV['TRACING_TAGS']) && !isset($this->config['tags'])) {
            $this->config['tags'] = $_ENV['TRACING_TAGS'];
        }

        // reporting
        if (isset($_ENV['TRACING_AGENT_HOST']) && !isset($this->config['local_agent']['reporting_host'])) {
            $this->config['local_agent']['reporting_host'] = $_ENV['TRACING_AGENT_HOST'];
        }

        if (isset($_ENV['TRACING_AGENT_PORT']) && !isset($this->config['local_agent']['reporting_port'])) {
            $this->config['local_agent']['reporting_port'] = intval($_ENV['TRACING_AGENT_PORT']);
        }

        if (isset($_ENV['TRACING_REPORTER_LOG_SPANS']) && !isset($this->config['logging'])) {
            $this->config['logging'] = filter_var($_ENV['TRACING_REPORTER_LOG_SPANS'], FILTER_VALIDATE_BOOLEAN);
        }

        if (isset($_ENV['TRACING_REPORTER_MAX_QUEUE_SIZE']) && !isset($this->config['max_buffer_length'])) {
            $this->config['max_buffer_length'] = (int) $_ENV['TRACING_REPORTER_MAX_QUEUE_SIZE'];
        }

        // sampling
        if (isset($_ENV['TRACING_SAMPLER_TYPE']) && !isset($this->config['sampler']['type'])) {
            $this->config['sampler']['type'] = $_ENV['TRACING_SAMPLER_TYPE'];
        }

        if (isset($_ENV['TRACING_SAMPLER_PARAM']) && !isset($this->config['sampler']['param'])) {
            $this->config['sampler']['param'] = $_ENV['TRACING_SAMPLER_PARAM'];
        }
    }
}
