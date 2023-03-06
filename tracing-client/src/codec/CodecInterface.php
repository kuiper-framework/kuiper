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

interface CodecInterface
{
    /**
     * Handle the logic behind injecting propagation scheme specific information into the carrier
     * (e.g. http request headers, amqp message headers, etc.).
     *
     * This method can modify the carrier.
     *
     * @see \kuiper\tracing\Tracer::inject
     *
     * @param SpanContext $spanContext
     * @param mixed       $carrier
     *
     * @return void
     */
    public function inject(SpanContext $spanContext, &$carrier): void;

    /**
     * Handle the logic behind extracting propagation-scheme specific information from carrier
     * (e.g. http request headers, amqp message headers, etc.).
     *
     * This method must not modify the carrier.
     *
     * @see \kuiper\tracing\Tracer::extract
     *
     * @param mixed $carrier
     *
     * @return SpanContext|null
     */
    public function extract($carrier): ?\OpenTracing\SpanContext;
}
