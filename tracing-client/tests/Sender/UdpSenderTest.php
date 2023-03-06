<?php

declare(strict_types=1);

namespace kuiper\tracing\Sender;

use kuiper\tracing\Span;
use kuiper\tracing\SpanContext;
use kuiper\tracing\Thrift\Agent\AgentClient;
use kuiper\tracing\Thrift\Agent\Zipkin\Annotation as ZipkinAnnotation;
use kuiper\tracing\Thrift\Agent\Zipkin\Span as ZipkinSpan;
use kuiper\tracing\Tracer;
use PHPUnit\Framework\TestCase;

class UdpSenderTest extends TestCase
{
    /**
     * @var UdpSender
     */
    private $sender;

    /**
     * @var AgentClient
     */
    private $client;

    public function setUp(): void
    {
        $this->client = $this->createMock(AgentClient::class);
        $this->sender = new UdpSender($this->client, 64000);
    }

    public function testMaxBufferLength(): void
    {
        $tracer = $this->createMock(Tracer::class);
        $tracer->method('getIpAddress')->willReturn('');
        $tracer->method('getServiceName')->willReturn('');

        $context = $this->createMock(SpanContext::class);

        $span = $this->createMock(Span::class);
        $span->method('getOperationName')->willReturn('dummy-operation');
        $span->method('getTracer')->willReturn($tracer);
        $span->method('getContext')->willReturn($context);

        $sender = new UdpSender($this->client, 100);

        $this->client
            ->expects(self::exactly(2))
            ->method('emitZipkinBatch')
            ->withConsecutive(
                [self::countOf(2)],
                [self::countOf(1)]
            );

        // one span has a length of ~25
        $sender->append($span); // 30 + 25 < 100 - chunk 1
        $sender->append($span); // 30 + 25 * 2 < 100 - chunk 1
        $sender->append($span); // 30 + 25 * 3 > 100 - chunk 2

        self::assertEquals(3, $sender->flush());
    }

    public function testFlush(): void
    {
        $this->assertEquals(0, $this->sender->flush());

        $logTimeStamp = (int) (microtime(true) * 1000000);

        $tracer = $this->createMock(Tracer::class);
        $tracer->method('getIpAddress')->willReturn('');
        $tracer->method('getServiceName')->willReturn('');
        $context = $this->createMock(SpanContext::class);
        $span = $this->createMock(Span::class);
        $span->method('getTracer')->willReturn($tracer);
        $span->method('getContext')->willReturn($context);
        $span
            ->expects($this->atLeastOnce())
            ->method('getLogs')
            ->willReturn([
                [
                    'timestamp' => $logTimeStamp,
                    'fields' => [
                        'foo' => 'bar',
                    ],
                ],
            ]);

        $this->client
            ->expects($this->once())
            ->method('emitZipkinBatch')
            ->with($this->callback(function ($spans) use ($logTimeStamp) {
                $this->assertCount(1, $spans);

                /* @var $annotation ZipkinSpan */
                $span = $spans[0];
                $this->assertInstanceOf(ZipkinSpan::class, $span);
                $this->assertCount(1, $span->annotations);

                /* @var $annotation ZipkinAnnotation */
                $annotation = $span->annotations[0];
                $this->assertInstanceOf(ZipkinAnnotation::class, $annotation);
                $this->assertSame($logTimeStamp, $annotation->timestamp);
                $this->assertSame(
                    json_encode([
                        'foo' => 'bar',
                    ]),
                    $annotation->value
                );

                return true;
            }));

        $this->sender->append($span);
        $this->assertEquals(1, $this->sender->flush());
    }
}
