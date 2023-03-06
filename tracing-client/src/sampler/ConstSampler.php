<?php

declare(strict_types=1);

namespace kuiper\tracing\sampler;

use kuiper\tracing\Constants;

/**
 * ConstSampler always returns the same decision.
 */
class ConstSampler implements SamplerInterface
{
    /**
     * Whether or not the new trace should be sampled.
     *
     * @var bool
     */
    private $decision;

    /**
     * A list of the sampler tags.
     *
     * @var array
     */
    private $tags = [];

    /**
     * ConstSampler constructor.
     *
     * @param bool $decision
     */
    public function __construct(bool $decision = true)
    {
        $this->tags = [
            Constants::SAMPLER_TYPE_TAG_KEY => Constants::SAMPLER_TYPE_CONST,
            Constants::SAMPLER_PARAM_TAG_KEY => $decision,
        ];

        $this->decision = $decision;
    }

    /**
     * {@inheritdoc}
     */
    public function isSampled(int $traceId, string $operation = ''): array
    {
        return [$this->decision, $this->tags];
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
