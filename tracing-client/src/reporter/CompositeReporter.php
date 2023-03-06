<?php

declare(strict_types=1);

namespace kuiper\tracing\reporter;

use kuiper\tracing\Span;

/**
 * CompositeReporter delegates reporting to one or more underlying reporters.
 */
class CompositeReporter implements ReporterInterface
{
    /**
     * @var ReporterInterface[]
     */
    private array $reporters;

    /**
     * CompositeReporter constructor.
     *
     * @param ReporterInterface ...$reporters
     */
    public function __construct(ReporterInterface ...$reporters)
    {
        $this->reporters = $reporters;
    }

    /**
     * {@inheritdoc}
     *
     * @param Span $span
     *
     * @return void
     */
    public function reportSpan(Span $span): void
    {
        foreach ($this->reporters as $reporter) {
            $reporter->reportSpan($span);
        }
    }

    /**
     * {@inheritdoc}
     *
     * @return void
     */
    public function close(): void
    {
        foreach ($this->reporters as $reporter) {
            $reporter->close();
        }
    }
}
