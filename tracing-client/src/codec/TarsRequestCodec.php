<?php

declare(strict_types=1);

/*
 * This file is part of the Kuiper package.
 *
 * (c) Ye Wenbin <wenbinye@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace kuiper\tracing\codec;

use Exception;
use kuiper\tars\client\TarsRequest;
use kuiper\tars\server\TarsServerRequest;
use kuiper\tracing\SpanContext;

class TarsRequestCodec implements CodecInterface
{
    public function __construct(private readonly TextCodec $textCodec)
    {
    }

    public function inject(SpanContext $spanContext, &$carrier): void
    {
        /** @var TarsRequest $carrier */
        $status = $carrier->getStatus();
        $this->textCodec->inject($spanContext, $status);
        $carrier->setStatus($status);
    }

    /**
     * @param TarsRequest|TarsServerRequest $carrier
     *
     * @return SpanContext|null
     *
     * @throws Exception
     */
    public function extract($carrier): ?SpanContext
    {
        return $this->textCodec->extract($carrier->getStatus());
    }
}
