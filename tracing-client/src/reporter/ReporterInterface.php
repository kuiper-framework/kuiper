<?php

declare(strict_types=1);

namespace kuiper\tracing\reporter;

use kuiper\tracing\Span;

/**
 * Uses to report finished span to something that collects those spans.
 */
interface ReporterInterface
{
    /**
     * Report finished span.
     *
     * @param Span $span
     *
     * @return void
     */
    public function reportSpan(Span $span): void;

    /**
     * Release any resources used by the reporter and flushes/sends the data.
     *
     * @return void
     */
    public function close(): void;
}
