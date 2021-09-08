<?php

declare(strict_types=1);

namespace kuiper\rpc\transporter;

use Psr\Http\Message\ResponseInterface;

interface Receivable
{
    /**
     * @return ResponseInterface
     */
    public function recv(): ResponseInterface;
}
