<?php

declare(strict_types=1);

namespace kuiper\tracing\Sampler;

use const Jaeger\SAMPLER_PARAM_TAG_KEY;
use const Jaeger\SAMPLER_TYPE_CONST;
use const Jaeger\SAMPLER_TYPE_TAG_KEY;

use PHPUnit\Framework\TestCase;

class ConstSamplerTest extends TestCase
{
    /**
     * @test
     *
     * @dataProvider samplerProvider
     *
     * @param bool  $decision
     * @param mixed $traceId
     */
    public function shouldDetermineWhetherOrTraceShouldBeSampled($decision, $traceId)
    {
        $sampler = new ConstSampler($decision);

        list($sampled, $tags) = $sampler->isSampled($traceId);

        $this->assertEquals($decision, $sampled);
        $this->assertEquals([
            SAMPLER_TYPE_TAG_KEY => SAMPLER_TYPE_CONST,
            SAMPLER_PARAM_TAG_KEY => $decision,
        ], $tags);

        $sampler->close();
    }

    public function samplerProvider()
    {
        return [
            [true,  1],
            [true,  PHP_INT_MAX],
            [false, 1],
            [false, PHP_INT_MAX],
        ];
    }
}
