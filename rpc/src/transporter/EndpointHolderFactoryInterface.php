<?php

declare(strict_types=1);

namespace kuiper\rpc\transporter;

interface EndpointHolderFactoryInterface
{
    /**
     * @param string $service
     *
     * @return EndpointHolderInterface
     */
    public function create(string $service): EndpointHolderInterface;
}
