<?php

namespace kuiper\rpc\server\util;

interface HealthyCheckServiceInterface
{
    /**
     * Checks service
     *
     * @return string
     */
    public function ping();
}
