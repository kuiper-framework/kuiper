<?php

declare(strict_types=1);

namespace kuiper\tars\server\monitor;

interface MonitorInterface
{
    /**
     * Send monitor metrics to tarsproperty Servant.
     */
    public function report(): void;
}
