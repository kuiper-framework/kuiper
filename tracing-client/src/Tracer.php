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

namespace kuiper\tracing;

use Exception;
use InvalidArgumentException;
use kuiper\swoole\coroutine\Coroutine;
use kuiper\tracing\codec\CodecInterface;
use kuiper\tracing\codec\Psr6RequestCodec;
use kuiper\tracing\codec\TextCodec;
use kuiper\tracing\reporter\ReporterInterface;
use kuiper\tracing\sampler\SamplerInterface;

use const OpenTracing\Formats\HTTP_HEADERS;
use const OpenTracing\Formats\TEXT_MAP;

use OpenTracing\NoopTracer;
use OpenTracing\Reference;
use OpenTracing\Scope as OTScope;
use OpenTracing\ScopeManager as OTScopeManager;
use OpenTracing\Span as OTSpan;
use OpenTracing\SpanContext as OTSpanContext;
use OpenTracing\StartSpanOptions;

use const OpenTracing\Tags\SPAN_KIND;
use const OpenTracing\Tags\SPAN_KIND_RPC_SERVER;

use OpenTracing\Tracer as OTTracer;
use OpenTracing\UnsupportedFormatException;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Throwable;

class Tracer implements OTTracer
{
    /**
     * @var string
     */
    private $serviceName;

    /**
     * @var ReporterInterface
     */
    private $reporter;

    /**
     * @var SamplerInterface
     */
    private $sampler;

    /**
     * @var string
     */
    private $ipAddress;

    /**
     * @var string
     */
    private $debugIdHeader;

    /**
     * @var CodecInterface[]
     */
    private $codecs;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var bool
     */
    private $oneSpanPerRpc;

    /**
     * @var string[]
     */
    private $tags;

    /**
     * @var ScopeManager
     */
    private $scopeManager;

    /**
     * @var callable
     */
    private static $TRACER_FACTORY;

    public static function set(callable $tracerFactory): void
    {
        self::$TRACER_FACTORY = $tracerFactory;
    }

    public static function get(): OTTracer
    {
        if (null === self::$TRACER_FACTORY) {
            return self::getNoopTracer();
        }
        $context = Coroutine::getContext();
        if (!isset($context['GLOBAL_TRACER'])) {
            $tracer = call_user_func(self::$TRACER_FACTORY);
            $tracer->tags[Constants::PID_TAG_KEY] = getmypid().'_'.Coroutine::getCoroutineId();
            $context['GLOBAL_TRACER'] = $tracer;
        }

        return $context['GLOBAL_TRACER'];
    }

    private static function getNoopTracer(): OTTracer
    {
        static $NOOP_TRACER;
        if (null === $NOOP_TRACER) {
            $NOOP_TRACER = new NoopTracer();
        }

        return $NOOP_TRACER;
    }

    /**
     * Tracer constructor.
     *
     * @param string               $serviceName
     * @param ReporterInterface    $reporter
     * @param SamplerInterface     $sampler
     * @param bool                 $oneSpanPerRpc
     * @param LoggerInterface|null $logger
     * @param ScopeManager|null    $scopeManager
     * @param string               $traceIdHeader
     * @param string               $baggageHeaderPrefix
     * @param string               $debugIdHeader
     * @param array|null           $tags
     */
    public function __construct(
        string $serviceName,
        ReporterInterface $reporter,
        SamplerInterface $sampler,
        bool $oneSpanPerRpc = true,
        LoggerInterface $logger = null,
        ScopeManager $scopeManager = null,
        string $traceIdHeader = Constants::TRACE_ID_HEADER,
        string $baggageHeaderPrefix = Constants::BAGGAGE_HEADER_PREFIX,
        string $debugIdHeader = Constants::DEBUG_ID_HEADER_KEY,
        ?array $tags = null,
        ?string $ipAddress = null
    ) {
        $this->serviceName = $serviceName;
        $this->reporter = $reporter;
        $this->sampler = $sampler;
        $this->oneSpanPerRpc = $oneSpanPerRpc;

        $this->logger = $logger ?? new NullLogger();
        $this->scopeManager = $scopeManager ?? new ScopeManager();

        $this->debugIdHeader = $debugIdHeader;

        $this->codecs = [
            TEXT_MAP => new TextCodec(
                false,
                $traceIdHeader,
                $baggageHeaderPrefix,
                $debugIdHeader
            ),
            HTTP_HEADERS => new Psr6RequestCodec(
                true,
                $traceIdHeader,
                $baggageHeaderPrefix,
                $debugIdHeader
            ),
        ];

        $this->tags = [
            Constants::JAEGER_VERSION_TAG_KEY => Constants::JAEGER_CLIENT_VERSION,
        ];
        if (null !== $tags) {
            $this->tags = array_merge($this->tags, $tags);
        }

        $this->ipAddress = $ipAddress;
    }

    /**
     * {@inheritdoc}
     */
    public function startSpan(string $operationName, $options = []): OTSpan
    {
        if (!($options instanceof StartSpanOptions)) {
            $options = StartSpanOptions::create($options);
        }

        $parent = $this->getParentSpanContext($options);
        $tags = $options->getTags();

        $rpcServer = ($tags[SPAN_KIND] ?? null) === SPAN_KIND_RPC_SERVER;

        $debugId = null;
        if (null === $parent || $parent->isDebugIdContainerOnly()) {
            $traceId = $this->randomId();
            $spanId = $traceId;
            $parentId = null;
            $flags = 0;
            $baggage = null;
            if (null === $parent) {
                [$sampled, $samplerTags] = $this->sampler->isSampled($traceId, $operationName);
                if ($sampled) {
                    $flags = Constants::SAMPLED_FLAG;
                    $tags = $tags ?? [];
                    foreach ($samplerTags as $key => $value) {
                        $tags[$key] = $value;
                    }
                }
            } else {  // have debug id
                $flags = Constants::SAMPLED_FLAG | Constants::DEBUG_FLAG;
                $tags = $tags ?? [];
                $tags[$this->debugIdHeader] = $parent->getDebugId();
            }
        } else {
            $traceId = $parent->getTraceId();
            if ($rpcServer && $this->oneSpanPerRpc) {
                // Zipkin-style one-span-per-RPC
                $spanId = $parent->getSpanId();
                $parentId = $parent->getParentId();
            } else {
                $spanId = $this->randomId();
                $parentId = $parent->getSpanId();
            }

            $flags = $parent->getFlags();
            $baggage = $parent->getBaggage();
        }

        $spanContext = new SpanContext(
            $traceId,
            $spanId,
            $parentId,
            $flags,
            $baggage,
            isset($parent) ? $parent->getDebugId() : null
        );

        $span = new Span(
            $spanContext,
            $this,
            $operationName,
            $tags ?? [],
            $options->getStartTime()
        );

        $mergedTags = array_merge($this->tags, $tags);
        $span->setTags($mergedTags);

        return $span;
    }

    /**
     * {@inheritdoc}
     */
    public function inject(OTSpanContext $spanContext, string $format, &$carrier): void
    {
        if ($spanContext instanceof SpanContext) {
            $codec = $this->codecs[$format] ?? null;

            if (null === $codec) {
                throw UnsupportedFormatException::forFormat($format);
            }

            $codec->inject($spanContext, $carrier);

            return;
        }

        $message = sprintf('Invalid span context. Expected '.__NAMESPACE__.'\\SpanContext, got %s.', get_class($spanContext));

        $this->logger->warning($message);
    }

    /**
     * {@inheritdoc}
     *
     * @param mixed $carrier
     *
     * @return SpanContext|null
     *
     * @throws UnsupportedFormatException
     */
    public function extract(string $format, $carrier): ?OTSpanContext
    {
        $codec = $this->codecs[$format] ?? null;

        if (null === $codec) {
            throw UnsupportedFormatException::forFormat($format);
        }

        try {
            return $codec->extract($carrier);
        } catch (Throwable $e) {
            $this->logger->warning($e->getMessage());

            return null;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function flush(): void
    {
        $this->sampler->close();
        $this->reporter->close();
    }

    public function reportSpan(Span $span): void
    {
        $this->reporter->reportSpan($span);
    }

    /**
     * {@inheritdoc}
     */
    public function getScopeManager(): OTScopeManager
    {
        return $this->scopeManager;
    }

    /**
     * {@inheritdoc}
     */
    public function getActiveSpan(): ?OTSpan
    {
        $activeScope = $this->getScopeManager()->getActive();
        if (null === $activeScope) {
            return null;
        }

        return $activeScope->getSpan();
    }

    /**
     * {@inheritdoc}
     */
    public function startActiveSpan(string $operationName, $options = []): OTScope
    {
        if (!$options instanceof StartSpanOptions) {
            $options = StartSpanOptions::create($options);
        }

        if (null === $this->getParentSpanContext($options) && null !== $this->getActiveSpan()) {
            $parent = $this->getActiveSpan()->getContext();
            $options = $options->withParent($parent);
        }

        $span = $this->startSpan($operationName, $options);

        return $this->scopeManager->activate($span, $options->shouldFinishSpanOnClose());
    }

    /**
     * Gets parent span context (if any).
     *
     * @param StartSpanOptions $options
     *
     * @return SpanContext|null
     */
    private function getParentSpanContext(StartSpanOptions $options): ?SpanContext
    {
        $references = $options->getReferences();
        foreach ($references as $ref) {
            if ($ref->isType(Reference::CHILD_OF)) {
                $spanContext = $ref->getSpanContext();
                if ($spanContext instanceof SpanContext) {
                    return $spanContext;
                } else {
                    throw new InvalidArgumentException('child_of not invalid');
                }
            }
        }

        return null;
    }

    /**
     * @return int
     *
     * @throws Exception
     */
    private function randomId(): int
    {
        return random_int(0, Constants::INT_MAX);
    }

    /**
     * @param SamplerInterface $sampler
     *
     * @return $this
     */
    public function setSampler(SamplerInterface $sampler): Tracer
    {
        $this->sampler = $sampler;

        return $this;
    }

    /**
     * @return string
     */
    public function getServiceName(): string
    {
        return $this->serviceName;
    }

    /**
     * @return string|null
     */
    public function getIpAddress(): ?string
    {
        return $this->ipAddress;
    }
}
