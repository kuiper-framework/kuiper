<?php

namespace kuiper\tracing\codec;

use kuiper\tars\client\TarsRequest;
use kuiper\tracing\SpanContext;

class TarsRequestCodec
{
    public function __construct(private readonly TextCodec $textCodec
    {
    }

    public function inject(SpanContext $spanContext, &$carrier): void
    {
        /** @var TarsRequest $carrier */
        $status = $carrier->getStatus();
        $this->textCodec->inject($spanContext, $status);
        $carrier->setStatus($status);
    }

    public function extract(TarsRequest $carrier): ?SpanContext
    {
        return $this->textCodec->extract($carrier->ge());
    }
}