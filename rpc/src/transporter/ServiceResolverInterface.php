<?php

declare(strict_types=1);

namespace kuiper\rpc\transporter;

interface ServiceResolverInterface
{
    /**
     * @param string $service
     *
     * @return ServiceEndpoint|null
     */
    public function resolve(string $service): ?ServiceEndpoint;
}
