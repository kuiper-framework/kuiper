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

namespace kuiper\tracing\codec;

use kuiper\tracing\SpanContext;
use OpenTracing\UnsupportedFormatException;

class BinaryCodec implements CodecInterface
{
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
        throw new UnsupportedFormatException('Binary encoding not implemented');
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
     * @throws UnsupportedFormatException
     */
    public function extract($carrier): ?\OpenTracing\SpanContext
    {
        throw new UnsupportedFormatException('Binary encoding not implemented');
    }
}
