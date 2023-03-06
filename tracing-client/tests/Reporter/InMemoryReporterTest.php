<?php

declare(strict_types=1);

namespace kuiper\tracing\Reporter;

use kuiper\tracing\Span;
use PHPUnit\Framework\TestCase;

class InMemoryReporterTest extends TestCase
{
    /** @test */
    public function shouldReportSpan()
    {
        /** @var \Jaeger\Span|\PHPUnit\Framework\MockObject\MockObject $span */
        $span = $this->createMock(Span::class);
        $reporter = new InMemoryReporter();

        $reporter->reportSpan($span);
        $reporter->close();

        $spans = $reporter->getSpans();
        $this->assertEquals([$span], $spans);
    }
}
