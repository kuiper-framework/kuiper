<?php

declare(strict_types=1);

namespace kuiper\tracing\reporter;

use kuiper\tracing\Span;

/**
 * NullReporter ignores all spans.
 */
class NullReporter implements ReporterInterface
{
    /**
     * {@inheritdoc}
     *
     * @param Span $span
     *
     * @return void
     */
    public function reportSpan(Span $span): void
    {
        // nothing to do
    }

    /**
     * {@inheritdoc}
     *
     * @return void
     */
    public function close(): void
    {
        // nothing to do
    }
}
