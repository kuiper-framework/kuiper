<?php

declare(strict_types=1);

namespace kuiper\tracing\middleware\tars;

use kuiper\tracing\Constants;

class TraceClientRequest extends \kuiper\tracing\middleware\rpc\TraceClientRequest
{
    public function __construct()
    {
        parent::__construct();
        $this->format = Constants::CODEC_TARS;
    }
}
