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

namespace kuiper\tracing;

class Constants
{
    public const INT_MAX = PHP_INT_MAX;

    // Max number of bits to use when generating random ID
    public const MAX_ID_BITS = 64;

    // How often remotely controller sampler polls for sampling strategy
    public const DEFAULT_SAMPLING_INTERVAL = 60;

    // How often remote reporter does a preemptive flush of its buffers
    public const DEFAULT_FLUSH_INTERVAL = 1;

    // Name of the HTTP header used to encode trace ID
    public const TRACE_ID_HEADER = 'uber-trace-id';

    // Prefix for HTTP headers used to record baggage items
    public const BAGGAGE_HEADER_PREFIX = 'uberctx-';

    // The name of HTTP header or a TextMap carrier key which, if found in the
    // carrier, forces the trace to be sampled as "debug" trace. The value of the
    // header is recorded as the tag on the # root span, so that the trace can
    // be found in the UI using this value as a correlation ID.
    public const DEBUG_ID_HEADER_KEY = 'jaeger-debug-id';

    public const JAEGER_CLIENT_VERSION = 'PHP-'.PHP_VERSION;

    // Tracer-scoped tag that tells the version of Jaeger client library
    public const JAEGER_VERSION_TAG_KEY = 'jaeger.version';

    // Tracer-scoped tag that contains the hostname
    public const JAEGER_HOSTNAME_TAG_KEY = 'jaeger.hostname';

    public const PID_TAG_KEY = 'jaeger.pid';

    public const SAMPLER_TYPE_TAG_KEY = 'sampler.type';

    public const SAMPLER_PARAM_TAG_KEY = 'sampler.param';

    public const DEFAULT_SAMPLING_PROBABILITY = 0.001;

    public const DEFAULT_LOWER_BOUND = 1.0 / (10.0 * 60.0); // sample once every 10 minutes

    public const DEFAULT_MAX_OPERATIONS = 2000;

    public const STRATEGIES_STR = 'perOperationStrategies';

    public const OPERATION_STR = 'operation';

    public const DEFAULT_LOWER_BOUND_STR = 'defaultLowerBoundTracesPerSecond';

    public const PROBABILISTIC_SAMPLING_STR = 'probabilisticSampling';

    public const SAMPLING_RATE_STR = 'samplingRate';

    public const DEFAULT_SAMPLING_PROBABILITY_STR = 'defaultSamplingProbability';

    public const OPERATION_SAMPLING_STR = 'operationSampling';

    public const MAX_TRACES_PER_SECOND_STR = 'maxTracesPerSecond';

    public const RATE_LIMITING_SAMPLING_STR = 'rateLimitingSampling';

    public const STRATEGY_TYPE_STR = 'strategyType';

    // the type of sampler that always makes the same decision.
    public const SAMPLER_TYPE_CONST = 'const';

    // the type of sampler that polls Jaeger agent for sampling strategy.
    public const SAMPLER_TYPE_REMOTE = 'remote';

    // the type of sampler that samples traces with a certain fixed probability.
    public const SAMPLER_TYPE_PROBABILISTIC = 'probabilistic';

    // the type of sampler that samples only up to a fixed number
    // of traces per second.
    // noinspection SpellCheckingInspection
    public const SAMPLER_TYPE_RATE_LIMITING = 'ratelimiting';

    // the type of sampler that samples only up to a fixed number
    // of traces per second.
    // noinspection SpellCheckingInspection
    public const SAMPLER_TYPE_LOWER_BOUND = 'lowerbound';

    public const DEFAULT_REPORTING_HOST = 'localhost';

    public const DEFAULT_REPORTING_PORT = 5775;

    public const DEFAULT_SAMPLING_PORT = 5778;

    public const LOCAL_AGENT_DEFAULT_ENABLED = true;

    public const ZIPKIN_SPAN_FORMAT = 'zipkin-span-format';

    public const SAMPLED_FLAG = 0x01;

    public const DEBUG_FLAG = 0x02;

    public const CODEC_TARS = 'tars';
}
