<?php

declare(strict_types=1);

namespace kuiper\tracing\reporter;

use kuiper\tracing\Span;

/**
 * InMemoryReporter stores spans in memory and returns them via getSpans().
 */
class InMemoryReporter implements ReporterInterface
{
    /**
     * @var Span[]
     */
    private array $spans = [];

    /**
     * {@inheritdoc}
     *
     * @param Span $span
     *
     * @return void
     */
    public function reportSpan(Span $span): void
    {
        $this->spans[] = $span;
    }

    /**
     * @return Span[]
     */
    public function getSpans(): array
    {
        return $this->spans;
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
