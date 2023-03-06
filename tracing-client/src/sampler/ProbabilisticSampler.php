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
use OutOfBoundsException;

/**
 * A sampler that randomly samples a certain percentage of traces specified
 * by the samplingRate, in the range between 0.0 and 1.0.
 */
class ProbabilisticSampler implements SamplerInterface
{
    /**
     * The sampling rate rate between 0.0 and 1.0.
     *
     * @var float
     */
    private $rate;

    /**
     * A list of the sampler tags.
     *
     * @var array
     */
    private $tags = [];

    /**
     * The boundary of the sample sampling rate.
     *
     * @var float
     */
    private $boundary;

    /**
     * ProbabilisticSampler constructor.
     *
     * @param float $rate
     *
     * @throws OutOfBoundsException
     */
    public function __construct(float $rate)
    {
        $this->tags = [
            Constants::SAMPLER_TYPE_TAG_KEY => Constants::SAMPLER_TYPE_PROBABILISTIC,
            Constants::SAMPLER_PARAM_TAG_KEY => $rate,
        ];

        if ($rate < 0.0 || $rate > 1.0) {
            throw new OutOfBoundsException('Sampling rate must be between 0.0 and 1.0.');
        }

        $this->rate = $rate;
        if ($rate < 0.5) {
            $this->boundary = (int) ($rate * PHP_INT_MAX);
        } else {
            // more precise calculation due to int and float having different precision near PHP_INT_MAX
            $this->boundary = PHP_INT_MAX - (int) ((1 - $rate) * PHP_INT_MAX);
        }
    }

    /**
     * {@inheritdoc}
     *
     * @param int    $traceId   the traceId on the span
     * @param string $operation the operation name set on the span
     *
     * @return array
     */
    public function isSampled(int $traceId, string $operation = ''): array
    {
        return [$traceId < $this->boundary, $this->tags];
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

    /**
     * @return float
     */
    public function getRate(): float
    {
        return $this->rate;
    }
}
