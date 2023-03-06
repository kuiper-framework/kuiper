<?php

declare(strict_types=1);

namespace kuiper\tracing;

use DateTime;
use DateTimeInterface;
use InvalidArgumentException;
use OpenTracing\Span as OTSpan;
use OpenTracing\SpanContext as OTSpanContext;

use const OpenTracing\Tags\COMPONENT;
use const OpenTracing\Tags\PEER_HOST_IPV4;
use const OpenTracing\Tags\PEER_PORT;
use const OpenTracing\Tags\PEER_SERVICE;
use const OpenTracing\Tags\SPAN_KIND;
use const OpenTracing\Tags\SPAN_KIND_RPC_CLIENT;
use const OpenTracing\Tags\SPAN_KIND_RPC_SERVER;

class Span implements OTSpan
{
    /**
     * @var Tracer
     */
    private $tracer;

    /**
     * @var SpanContext
     */
    private $context;

    /**
     * @var string
     */
    private $operationName;

    /**
     * @var int
     */
    private $startTime;

    /**
     * @var int|null
     */
    private $endTime;

    /**
     * SPAN_RPC_CLIENT.
     *
     * @var string|null
     */
    private $kind;

    /**
     * @var array|null
     */
    public $peer;

    /**
     * @var string|null
     */
    private $component;

    /**
     * @var array
     */
    private $logs = [];

    /**
     * @var array
     */
    public $tags = [];

    /**
     * @var bool
     */
    private $debug = false;

    /**
     * Span constructor.
     *
     * @param SpanContext             $context
     * @param Tracer                  $tracer
     * @param string                  $operationName
     * @param array                   $tags
     * @param int|float|DateTime|null $startTime
     */
    public function __construct(
        SpanContext $context,
        Tracer $tracer,
        string $operationName,
        array $tags = [],
        $startTime = null
    ) {
        $this->context = $context;
        $this->tracer = $tracer;

        $this->operationName = $operationName;
        $this->startTime = $this->microTime($startTime);
        $this->peer = null;

        foreach ($tags as $key => $value) {
            $this->setTag($key, $value);
        }
    }

    /**
     * Converts time to microtime int
     *  - int represents microseconds
     *  - float represents seconds.
     *
     * @param int|float|DateTime|mixed|null $time
     *
     * @return int
     */
    protected function microTime($time): int
    {
        if (null === $time) {
            return $this->timestampMicro();
        }

        if ($time instanceof DateTimeInterface) {
            return (int) round(((float) $time->format('U.u')) * 1000000);
        }

        if (is_int($time)) {
            return $time;
        }

        if (is_float($time)) {
            return (int) round($time * 1000000, 0);
        }

        throw new InvalidArgumentException(sprintf('Time should be one of the types int|float|DateTime|null, got %s.', gettype($time)));
    }

    /**
     * @return Tracer
     */
    public function getTracer(): Tracer
    {
        return $this->tracer;
    }

    /**
     * @return bool
     */
    public function isDebug(): bool
    {
        return $this->debug;
    }

    /**
     * @return int
     */
    public function getStartTime(): int
    {
        return $this->startTime;
    }

    /**
     * @return int|null
     */
    public function getEndTime(): ?int
    {
        return $this->endTime;
    }

    /**
     * @return string
     */
    public function getOperationName(): string
    {
        return $this->operationName;
    }

    /**
     * @return mixed
     */
    public function getComponent()
    {
        // TODO
        return $this->component;
    }

    /**
     * {@inheritdoc}
     *
     * @return SpanContext
     */
    public function getContext(): OTSpanContext
    {
        return $this->context;
    }

    /**
     * {@inheritdoc}
     */
    public function finish($finishTime = null, array $logRecords = []): void
    {
        if (!$this->isSampled()) {
            return;
        }

        foreach ($logRecords as $logRecord) {
            $this->log($logRecord);
        }

        $this->endTime = $this->microTime($finishTime);
        $this->tracer->reportSpan($this);
    }

    /**
     * Returns true if the trace should be measured.
     *
     * @return bool
     */
    public function isSampled(): bool
    {
        $context = $this->getContext();

        return ($context->getFlags() & Constants::SAMPLED_FLAG) === Constants::SAMPLED_FLAG;
    }

    /**
     * {@inheritdoc}
     */
    public function overwriteOperationName(string $newOperationName): void
    {
        // TODO log warning
        $this->operationName = $newOperationName;
    }

    /**
     * @param array $tags
     *
     * @return void
     */
    public function setTags(array $tags): void
    {
        foreach ($tags as $key => $value) {
            $this->setTag($key, $value);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function setTag(string $key, $value): void
    {
        if ($this->isSampled()) {
            $special = self::SPECIAL_TAGS[$key] ?? null;
            $handled = false;

            if (null !== $special && is_callable([$this, $special])) {
                $handled = $this->$special($value);
            }

            if (!$handled) {
                $this->tags[$key] = $value;
            }
        }
    }

    private const SPECIAL_TAGS = [
        PEER_SERVICE => 'setPeerService',
        PEER_HOST_IPV4 => 'setPeerHostIpv4',
        PEER_PORT => 'setPeerPort',
        SPAN_KIND => 'setSpanKind',
        COMPONENT => 'setComponent',
    ];

    /**
     * Sets a low-cardinality identifier of the module, library,
     * or package that is generating a span.
     *
     * @see Span::setTag()
     *
     * @param string|null $value
     *
     * @return bool
     */
    private function setComponent($value): bool
    {
        $this->component = $value;

        return true;
    }

    /**
     * @param string|null $value
     *
     * @return bool
     */
    private function setSpanKind($value): bool
    {
        if (null === $value || SPAN_KIND_RPC_CLIENT === $value || SPAN_KIND_RPC_SERVER === $value) {
            $this->kind = $value;

            return true;
        }

        return false;
    }

    /**
     * @param string|null $value
     *
     * @return bool
     */
    private function setPeerPort($value): bool
    {
        if (null === $this->peer) {
            $this->peer = ['port' => $value];
        } else {
            $this->peer['port'] = $value;
        }

        return true;
    }

    /**
     * @param string|null $value
     *
     * @return bool
     */
    private function setPeerHostIpv4($value): bool
    {
        if (null === $this->peer) {
            $this->peer = ['ipv4' => $value];
        } else {
            $this->peer['ipv4'] = $value;
        }

        return true;
    }

    /**
     * @param string|null $value
     *
     * @return bool
     */
    private function setPeerService($value): bool
    {
        if (null === $this->peer) {
            $this->peer = ['service_name' => $value];
        } else {
            $this->peer['service_name'] = $value;
        }

        return true;
    }

    /**
     * @return bool
     */
    public function isRpc(): bool
    {
        return SPAN_KIND_RPC_CLIENT === $this->kind || SPAN_KIND_RPC_SERVER === $this->kind;
    }

    /**
     * @return bool
     */
    public function isRpcClient(): bool
    {
        return SPAN_KIND_RPC_CLIENT === $this->kind;
    }

    /**
     * {@inheritdoc}
     */
    public function log(array $fields = [], $timestamp = null): void
    {
        $timestamp = $this->microTime($timestamp);
        if ($timestamp < $this->getStartTime()) {
            $timestamp = $this->timestampMicro();
        }

        $this->logs[] = [
            'fields' => $fields,
            'timestamp' => $timestamp,
        ];
    }

    /**
     * Returns the logs.
     *
     * [
     *      [
     *          'timestamp' => timestamp in microsecond,
     *          'fields' => [
     *              'error' => 'message',
     *          ]
     *      ]
     * ]
     *
     * @return array
     */
    public function getLogs(): array
    {
        return $this->logs;
    }

    /**
     * {@inheritdoc}
     */
    public function addBaggageItem(string $key, string $value): void
    {
        $this->context = $this->context->withBaggageItem($key, $value);
    }

    /**
     * {@inheritdoc}
     */
    public function getBaggageItem(string $key): ?string
    {
        return $this->context->getBaggageItem($key);
    }

    /**
     * @return array
     */
    public function getTags(): array
    {
        return $this->tags;
    }

    /**
     * @return int
     */
    private function timestampMicro(): int
    {
        return (int) round(microtime(true) * 1000000);
    }
}
