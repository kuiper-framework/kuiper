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

namespace kuiper\tracing\sampler;

/**
 * Sampler is responsible for deciding if a new trace should be sampled and captured for storage.
 */
interface SamplerInterface
{
    /**
     * Whether or not the new trace should be sampled.
     *
     * Implementations should return an array in the format [$decision, $tags].
     *
     * @param int    $traceId   the traceId on the span
     * @param string $operation the operation name set on the span
     *
     * @return array
     */
    public function isSampled(int $traceId, string $operation): array;

    /**
     * Release any resources used by the sampler.
     *
     * @return void
     */
    public function close(): void;
}
