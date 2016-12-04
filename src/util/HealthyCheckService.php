<?php

namespace kuiper\rpc\server\util;

class HealthyCheckService implements HealthyCheckServiceInterface
{
    /**
     * {@inheritdoc}
     */
    public function ping()
    {
        return 'pong';
    }
}
