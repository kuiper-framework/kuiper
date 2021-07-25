<?php

declare(strict_types=1);

namespace kuiper\rpc\transporter;

use kuiper\rpc\exception\CannotResolveEndpointException;

interface EndpointHolderInterface
{
    /**
     * @return Endpoint
     *
     * @throws CannotResolveEndpointException
     */
    public function get(): Endpoint;
}
