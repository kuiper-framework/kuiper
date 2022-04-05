<?php

declare(strict_types=1);

namespace kuiper\rpc\servicediscovery;

use kuiper\rpc\servicediscovery\dns\DnsResolverInterface;
use kuiper\rpc\ServiceLocator;
use kuiper\rpc\transporter\Endpoint;

class DnsServiceResolver implements ServiceResolverInterface
{
    /**
     * @var DnsResolverInterface
     */
    private $dnsResolver;

    /**
     * DnsServiceResolver constructor.
     *
     * @param DnsResolverInterface $dnsResolver
     */
    public function __construct(DnsResolverInterface $dnsResolver)
    {
        $this->dnsResolver = $dnsResolver;
    }

    public function resolve(ServiceLocator $serviceLocator): ?ServiceEndpoint
    {
        $records = $this->getDnsRecords($serviceLocator);
        if (empty($records)) {
            return null;
        }
        $endpoints = [];
        foreach ($records  as $record) {
            $endpoint = new Endpoint(
                'tcp',
                $record['target'],
                $record['port'],
                null,
                null,
                ['weight' => $record['weight']]
            );
            $endpoints[] = $endpoint;
        }

        return new ServiceEndpoint($serviceLocator, $endpoints);
    }

    protected function getDnsRecords(ServiceLocator $serviceLocator): array
    {
        return $this->dnsResolver->resolve($this->getServiceHost($serviceLocator));
    }

    protected function getServiceHost(ServiceLocator $serviceLocator): string
    {
        [$app, $server] = explode('.', strtolower($serviceLocator->getName()));

        return "{$app}-{$server}-tarsrpc";
    }
}
