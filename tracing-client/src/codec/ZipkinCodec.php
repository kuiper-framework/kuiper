<?php

declare(strict_types=1);

namespace kuiper\tracing\codec;

use kuiper\tracing\Constants;
use kuiper\tracing\SpanContext;

class ZipkinCodec implements CodecInterface
{
    private const SAMPLED_NAME = 'X-B3-Sampled';
    private const TRACE_ID_NAME = 'X-B3-TraceId';
    private const SPAN_ID_NAME = 'X-B3-SpanId';
    private const PARENT_ID_NAME = 'X-B3-ParentSpanId';
    private const FLAGS_NAME = 'X-B3-Flags';

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
        $carrier[self::TRACE_ID_NAME] = dechex($spanContext->getTraceId());
        $carrier[self::SPAN_ID_NAME] = dechex($spanContext->getSpanId());
        if (null !== $spanContext->getParentId()) {
            $carrier[self::PARENT_ID_NAME] = dechex($spanContext->getParentId());
        }
        $carrier[self::FLAGS_NAME] = (int) $spanContext->getFlags();
    }

    /**
     * {@inheritdoc}
     *
     * @see \kuiper\tracing\Tracer::extract
     *
     * @param mixed $carrier
     *
     * @return SpanContext|null
     */
    public function extract($carrier): ?\OpenTracing\SpanContext
    {
        $traceId = '0';
        $spanId = '0';
        $parentId = '0';
        $flags = 0;

        if (isset($carrier[strtolower(self::SAMPLED_NAME)])) {
            if ('1' === $carrier[strtolower(self::SAMPLED_NAME)] ||
                'true' === strtolower($carrier[strtolower(self::SAMPLED_NAME)])) {
                $flags |= Constants::SAMPLED_FLAG;
            }
        }

        if (isset($carrier[strtolower(self::TRACE_ID_NAME)])) {
            $traceId = CodecUtility::hexToInt64($carrier[strtolower(self::TRACE_ID_NAME)]);
        }

        if (isset($carrier[strtolower(self::PARENT_ID_NAME)])) {
            $parentId = CodecUtility::hexToInt64($carrier[strtolower(self::PARENT_ID_NAME)]);
        }

        if (isset($carrier[strtolower(self::SPAN_ID_NAME)])) {
            $spanId = CodecUtility::hexToInt64($carrier[strtolower(self::SPAN_ID_NAME)]);
        }

        if (isset($carrier[strtolower(self::FLAGS_NAME)])) {
            if ('1' === $carrier[strtolower(self::FLAGS_NAME)]) {
                $flags |= Constants::DEBUG_FLAG;
            }
        }

        if (0 !== $traceId && 0 !== $spanId) {
            return new SpanContext($traceId, $spanId, $parentId, $flags);
        }

        return null;
    }
}
