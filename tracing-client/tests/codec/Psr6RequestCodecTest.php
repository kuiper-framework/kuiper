<?php

declare(strict_types=1);

namespace kuiper\tracing\codec;

use kuiper\tracing\Constants;
use kuiper\tracing\SpanContext;
use Laminas\Diactoros\Request;
use PHPUnit\Framework\TestCase;

class Psr6RequestCodecTest extends TestCase
{
    private $psr6Codec;

    public function setUp(): void
    {
        $this->psr6Codec = new Psr6RequestCodec();
    }

    public function testCanInjectSimpleContextInCarrier(): void
    {
        $context = new SpanContext(1, 1, null, null);
        $carrier = new Request();

        $this->psr6Codec->inject($context, $carrier);

        $this->assertCount(1, $carrier->getHeaders());
        $this->assertArrayHasKey(Constants::TRACE_ID_HEADER, $carrier->getHeaders());
        $this->assertEquals('1:1:0:0', $carrier->getHeaderLine(Constants::TRACE_ID_HEADER));
    }
}
