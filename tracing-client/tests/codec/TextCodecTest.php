<?php

declare(strict_types=1);

namespace kuiper\tracing\codec;

use Exception;
use kuiper\tracing\Constants;
use kuiper\tracing\SpanContext;
use PHPUnit\Framework\TestCase;

class TextCodecTest extends TestCase
{
    /** @var TextCodec */
    private $textCodec;

    public function setUp(): void
    {
        $this->textCodec = new TextCodec();
    }

    public function testCanInjectSimpleContextInCarrier(): void
    {
        $context = new SpanContext(1, 1, null, null);
        $carrier = [];

        $this->textCodec->inject($context, $carrier);

        $this->assertCount(1, $carrier);
        $this->assertArrayHasKey(Constants::TRACE_ID_HEADER, $carrier);
        $this->assertEquals('1:1:0:0', $carrier[Constants::TRACE_ID_HEADER]);
    }

    /**
     * @dataProvider contextDataProvider
     *
     * @param bool $urlEncode
     * @param $baggage
     */
    public function testCanInjectContextBaggageInCarrier(bool $urlEncode, $baggage, $injectedBaggage): void
    {
        $carrier = [];

        $context = new SpanContext(1, 1, null, null, $baggage);
        $textCodec = new TextCodec($urlEncode);
        $textCodec->inject($context, $carrier);

        $this->assertCount(1 + count($baggage), $carrier);
        $this->assertArrayHasKey(Constants::TRACE_ID_HEADER, $carrier);
        foreach ($injectedBaggage as $key => $value) {
            $this->assertArrayHasKey(Constants::BAGGAGE_HEADER_PREFIX.$key, $carrier);
            $this->assertEquals($carrier[Constants::BAGGAGE_HEADER_PREFIX.$key], $value);
        }
    }

    public function contextDataProvider()
    {
        return [
            [false, ['baggage-1' => 'baggage value'], ['baggage-1' => 'baggage value']],
            [false, ['baggage-1' => 'https://testdomain.sk'], ['baggage-1' => 'https://testdomain.sk']],
            [true, ['baggage-1' => 'https://testdomain.sk'], ['baggage-1' => 'https%3A%2F%2Ftestdomain.sk']],
        ];
    }

    /**
     * @dataProvider carrierDataProvider
     *
     * @param $urlEncode
     * @param $carrier
     * @param $traceId
     * @param $spanId
     * @param $parentId
     * @param $flags
     * @param $baggage
     *
     * @throws Exception
     */
    public function testSpanContextParsingFromHeader($urlEncode, $carrier, $traceId, $spanId, $parentId, $flags, $baggage): void
    {
        $textCodec = new TextCodec($urlEncode);
        $spanContext = $textCodec->extract($carrier);

        $this->assertEquals($traceId, $spanContext->getTraceId());
        $this->assertEquals($spanId, $spanContext->getSpanId());
        $this->assertEquals($parentId, $spanContext->getParentId());
        $this->assertEquals($flags, $spanContext->getFlags());
        $this->assertCount(count($baggage), $spanContext->getBaggage() ? $spanContext->getBaggage() : []);
        foreach ($baggage as $key => $value) {
            $this->assertEquals($value, $spanContext->getBaggageItem($key));
        }
    }

    public function carrierDataProvider(): array
    {
        return [
            [
                false,
                [
                    Constants::TRACE_ID_HEADER => '32834e4115071776:f7802330248418d:f123456789012345:1',
                ],
                '3639838965278119798',
                '1114643325879075213',
                '-1070935975401544891',
                1,
                [],
            ],
            [
                false,
                [
                    Constants::TRACE_ID_HEADER => '32834e4115071776:f7802330248418d:f123456789012345:1',
                    Constants::BAGGAGE_HEADER_PREFIX.'baggage-1' => 'https://testdomain.sk',
                ],
                '3639838965278119798',
                '1114643325879075213',
                '-1070935975401544891',
                1,
                ['baggage-1' => 'https://testdomain.sk'],
            ],
            [
                true,
                [
                    Constants::TRACE_ID_HEADER => '32834e4115071776:f7802330248418d:f123456789012345:1',
                    Constants::BAGGAGE_HEADER_PREFIX.'baggage-1' => 'https%3A%2F%2Ftestdomain.sk',
                ],
                '3639838965278119798',
                '1114643325879075213',
                '-1070935975401544891',
                1,
                ['baggage-1' => 'https://testdomain.sk'],
            ],
        ];
    }

    public function testBaggageWithoutTraceContext(): void
    {
        $carrier = [Constants::BAGGAGE_HEADER_PREFIX.'test' => 'some data'];

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('baggage without trace ctx');

        $this->textCodec->extract($carrier);
    }

    public function testInvalidSpanContextParsingFromHeader(): void
    {
        $carrier = [Constants::TRACE_ID_HEADER => 'invalid_data'];

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Malformed tracer state string.');

        $this->textCodec->extract($carrier);
    }

    public function testExtractDebugSpanContext(): void
    {
        $carrier = [Constants::DEBUG_ID_HEADER_KEY => 'debugId'];

        $spanContext = $this->textCodec->extract($carrier);

        $this->assertEquals('debugId', $spanContext->getDebugId());
        $this->assertNull($spanContext->getTraceId());
        $this->assertNull($spanContext->getSpanId());
        $this->assertNull($spanContext->getParentId());
        $this->assertNull($spanContext->getFlags());
    }

    public function testExtractEmptySpanContext(): void
    {
        $spanContext = $this->textCodec->extract([]);
        $this->assertNull($spanContext);
    }
}
