<?php

declare(strict_types=1);

namespace kuiper\rpc\client;

interface RequestIdGeneratorInterface
{
    /**
     * Generate request id.
     *
     * @return int
     */
    public function next(): int;
}
