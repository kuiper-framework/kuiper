<?php

declare(strict_types=1);

namespace kuiper\rpc\transporter;

interface Refreshable
{
    /**
     * @param bool $force
     */
    public function refresh(bool $force = false): void;
}
