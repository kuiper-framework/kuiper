<?php

declare(strict_types=1);

namespace kuiper\rpc;

interface Closable
{
    /**
     * close resource.
     */
    public function close(): void;
}
