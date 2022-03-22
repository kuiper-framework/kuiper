<?php

declare(strict_types=1);

namespace kuiper\rpc\transporter;

use kuiper\rpc\Closable;
use Psr\Http\Message\ResponseInterface;

interface Session extends Closable
{
    public function recv(): ResponseInterface;
}
