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

use kuiper\tracing\Constants;
use kuiper\tracing\RateLimiter;

class RateLimitingSampler implements SamplerInterface
{
    /**
     * @var RateLimiter
     */
    private $rateLimiter;

    /**
     * A list of the sampler tags.
     *
     * @var array
     */
    private $tags = [];

    /**
     * RateLimitingSampler constructor.
     *
     * @param int         $maxTracesPerSecond
     * @param RateLimiter $rateLimiter
     */
    public function __construct(int $maxTracesPerSecond, RateLimiter $rateLimiter)
    {
        $this->tags = [
            Constants::SAMPLER_TYPE_TAG_KEY => Constants::SAMPLER_TYPE_RATE_LIMITING,
            Constants::SAMPLER_PARAM_TAG_KEY => $maxTracesPerSecond,
        ];

        $maxTracesPerNanosecond = $maxTracesPerSecond / 1000000000.0;
        $this->rateLimiter = $rateLimiter;
        $this->rateLimiter->initialize($maxTracesPerNanosecond, $maxTracesPerSecond > 1.0 ? 1.0 : $maxTracesPerSecond);
    }

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
    public function isSampled(int $traceId, string $operation): array
    {
        return [$this->rateLimiter->checkCredit(1.0), $this->tags];
    }

    /**
     * {@inheritdoc}
     *
     * Only implemented to satisfy the sampler interface.
     *
     * @return void
     */
    public function close(): void
    {
        // nothing to do
    }
}
