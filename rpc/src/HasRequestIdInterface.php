<?php

declare(strict_types=1);

namespace kuiper\rpc;

interface HasRequestIdInterface
{
    public function getRequestId(): int;
}
