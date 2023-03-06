<?php

declare(strict_types=1);

namespace kuiper\tracing;

use ArrayIterator;
use kuiper\tracing\codec\TextCodec;
use OpenTracing\SpanContext as OTSpanContext;
use Traversable;

class SpanContext implements OTSpanContext
{
    /**
     * @var int|null
     */
    private $traceId;

    /**
     * @var int|null
     */
    private $spanId;
    /**
     * @var int|null
     */
    private $parentId;

    /**
     * @var int|null
     */
    private $flags;

    /**
     * @var array
     */
    private $baggage;

    /**
     * @var string|null
     */
    private $debugId;

    /**
     * SpanContext constructor.
     *
     * @param int|null    $traceId
     * @param int|null    $spanId
     * @param int|null    $parentId
     * @param int|null    $flags
     * @param array|null  $baggage
     * @param string|null $debugId
     */
    public function __construct(?int $traceId, ?int $spanId, ?int $parentId, ?int $flags = null, ?array $baggage = null, ?string $debugId = null)
    {
        $this->traceId = $traceId;
        $this->spanId = $spanId;
        $this->parentId = $parentId;
        $this->flags = $flags;
        $this->baggage = $baggage ?? [];
        $this->debugId = $debugId;
    }

    /**
     * {@inheritdoc}
     */
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->baggage);
    }

    /**
     * {@inheritdoc}
     */
    public function getBaggageItem(string $key): ?string
    {
        return $this->baggage[$key] ?? null;
    }

    /**
     * {@inheritdoc}
     *
     * @param string $key
     * @param string $value
     *
     * @return SpanContext
     */
    public function withBaggageItem(string $key, string $value): OTSpanContext
    {
        return new self(
            $this->traceId,
            $this->spanId,
            $this->parentId,
            $this->flags,
            [$key => $value] + $this->baggage
        );
    }

    public function getTraceId(): ?int
    {
        return $this->traceId;
    }

    public function getParentId(): ?int
    {
        return $this->parentId;
    }

    public function getSpanId(): ?int
    {
        return $this->spanId;
    }

    /**
     * Get the span context flags.
     *
     * @return int|null
     */
    public function getFlags(): ?int
    {
        return $this->flags;
    }

    public function getBaggage(): array
    {
        return $this->baggage;
    }

    public function getDebugId(): ?string
    {
        return $this->debugId;
    }

    public function isDebugIdContainerOnly(): bool
    {
        return (null === $this->traceId) && (null !== $this->debugId);
    }

    public function getContextId(): string
    {
        return TextCodec::spanContextToString($this->traceId, $this->spanId, $this->parentId, $this->flags);
    }
}
