<?php

declare(strict_types=1);

namespace kuiper\tracing\codec;

use Exception;
use InvalidArgumentException;
use kuiper\tracing\Constants;
use kuiper\tracing\SpanContext;

class TextCodec implements CodecInterface
{
    /**
     * @var bool
     */
    private $urlEncoding;
    /**
     * @var string
     */
    private $traceIdHeader;
    /**
     * @var string
     */
    private $baggagePrefix;
    /**
     * @var string
     */
    private $debugIdHeader;
    /**
     * @var int
     */
    private $prefixLength;

    /**
     * @param bool   $urlEncoding
     * @param string $traceIdHeader
     * @param string $baggageHeaderPrefix
     * @param string $debugIdHeader
     */
    public function __construct(
        bool $urlEncoding = false,
        string $traceIdHeader = Constants::TRACE_ID_HEADER,
        string $baggageHeaderPrefix = Constants::BAGGAGE_HEADER_PREFIX,
        string $debugIdHeader = Constants::DEBUG_ID_HEADER_KEY
    ) {
        $this->urlEncoding = $urlEncoding;
        $this->traceIdHeader = str_replace('_', '-', strtolower($traceIdHeader));
        $this->baggagePrefix = str_replace('_', '-', strtolower($baggageHeaderPrefix));
        $this->debugIdHeader = str_replace('_', '-', strtolower($debugIdHeader));
        $this->prefixLength = strlen($baggageHeaderPrefix);
    }

    /**
     * {@inheritdoc}
     *
     * @see \kuiper\tracing\Tracer::inject
     *
     * @param SpanContext $spanContext
     * @param mixed       $carrier
     *
     * @return void
     */
    public function inject(SpanContext $spanContext, &$carrier): void
    {
        $carrier[$this->traceIdHeader] = self::spanContextToString(
            $spanContext->getTraceId(),
            $spanContext->getSpanId(),
            $spanContext->getParentId(),
            $spanContext->getFlags()
        );
        if (null !== $spanContext->getDebugId()) {
            $carrier[$this->debugIdHeader] = $spanContext->getDebugId();
        }

        $baggage = $spanContext->getBaggage();
        if (empty($baggage)) {
            return;
        }

        foreach ($baggage as $key => $value) {
            $encodedValue = $value;

            if ($this->urlEncoding) {
                $encodedValue = urlencode($value);
            }

            $carrier[$this->baggagePrefix.$key] = $encodedValue;
        }
    }

    /**
     * {@inheritdoc}
     *
     * @see \kuiper\tracing\Tracer::extract
     *
     * @param mixed $carrier
     *
     * @return SpanContext|null
     *
     * @throws Exception
     */
    public function extract($carrier): ?\OpenTracing\SpanContext
    {
        $traceId = null;
        $spanId = null;
        $parentId = null;
        $flags = null;
        $baggage = [];
        $debugId = null;

        foreach ($carrier as $key => $value) {
            $ucKey = strtolower($key);

            if ($ucKey === $this->traceIdHeader) {
                if ($this->urlEncoding) {
                    $value = urldecode($value);
                }
                [$traceId, $spanId, $parentId, $flags] = $this->spanContextFromString($value);
            } elseif ($this->startsWith($ucKey, $this->baggagePrefix)) {
                if ($this->urlEncoding) {
                    $value = urldecode($value);
                }
                $attrKey = substr($key, $this->prefixLength);
                $baggage[strtolower($attrKey)] = $value;
            } elseif ($ucKey === $this->debugIdHeader) {
                if ($this->urlEncoding) {
                    $value = urldecode($value);
                }
                $debugId = $value;
            }
        }

        if (null === $traceId && !empty($baggage)) {
            throw new InvalidArgumentException('baggage without trace ctx');
        }

        if (null === $traceId) {
            if (null !== $debugId) {
                return new SpanContext(null, null, null, null, [], $debugId);
            }

            return null;
        }

        return new SpanContext($traceId, $spanId, $parentId, $flags, $baggage);
    }

    /**
     * Store a span context to a string.
     *
     * @param int|null $traceId
     * @param int|null $spanId
     * @param int|null $parentId
     * @param int|null $flags
     *
     * @return string
     */
    public static function spanContextToString(?int $traceId, ?int $spanId, ?int $parentId, ?int $flags): string
    {
        return sprintf('%016x:%016x:%016x:%x', $traceId ?? 0, $spanId ?? 0, $parentId ?? 0, $flags ?? 0);
    }

    /**
     * Create a span context from a string.
     *
     * @param string $value
     *
     * @return array
     *
     * @throws Exception
     */
    private function spanContextFromString(string $value): array
    {
        $parts = explode(':', $value);

        if (4 !== count($parts)) {
            throw new InvalidArgumentException('Malformed tracer state string.');
        }

        return [
            CodecUtility::hexToInt64($parts[0]),
            CodecUtility::hexToInt64($parts[1]),
            CodecUtility::hexToInt64($parts[2]),
            CodecUtility::hexToInt64($parts[3]),
        ];
    }

    /**
     * Checks that a string ($haystack) starts with a given prefix ($needle).
     *
     * @param string $haystack
     * @param string $needle
     *
     * @return bool
     */
    private function startsWith(string $haystack, string $needle): bool
    {
        return 0 === strpos($haystack, $needle);
    }
}
