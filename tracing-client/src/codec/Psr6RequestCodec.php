<?php

declare(strict_types=1);

namespace kuiper\tracing\codec;

use kuiper\tracing\SpanContext;
use Psr\Http\Message\RequestInterface;
use Webmozart\Assert\Assert;

class Psr6RequestCodec extends TextCodec
{
    /**
     * {@inheritdoc}
     */
    public function inject(SpanContext $spanContext, &$carrier): void
    {
        Assert::isInstanceOf($carrier, RequestInterface::class);
        /** @var RequestInterface $carrier */
        $headers = [];
        parent::inject($spanContext, $headers);
        foreach ($headers as $key => $value) {
            $carrier = $carrier->withHeader($key, $value);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function extract($carrier): ?\OpenTracing\SpanContext
    {
        Assert::isInstanceOf($carrier, RequestInterface::class);
        /** @var RequestInterface $carrier */
        return parent::extract(array_map(static function ($values) {
            return current($values);
        }, $carrier->getHeaders()));
    }
}
