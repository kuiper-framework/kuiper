<?php

declare(strict_types=1);

namespace kuiper\rpc\client\middleware;

use kuiper\helper\Arrays;
use kuiper\helper\Text;
use kuiper\rpc\MiddlewareInterface;
use kuiper\rpc\RpcRequestHandlerInterface;
use kuiper\rpc\RpcRequestInterface;
use kuiper\rpc\RpcResponseInterface;
use kuiper\rpc\servicediscovery\InMemoryCache;
use kuiper\rpc\servicediscovery\loadbalance\Equality;
use kuiper\rpc\servicediscovery\loadbalance\LoadBalanceAlgorithm;
use kuiper\rpc\servicediscovery\loadbalance\LoadBalanceInterface;
use kuiper\rpc\servicediscovery\loadbalance\Random;
use kuiper\rpc\servicediscovery\loadbalance\RoundRobin;
use kuiper\rpc\servicediscovery\ServiceEndpoint;
use kuiper\rpc\servicediscovery\ServiceResolverInterface;
use kuiper\rpc\ServiceLocator;
use kuiper\rpc\transporter\Endpoint;
use Psr\SimpleCache\CacheInterface;
use Webmozart\Assert\Assert;

class ServiceDiscovery implements MiddlewareInterface
{
    /**
     * @var ServiceResolverInterface
     */
    private $serviceResolver;

    /**
     * @var CacheInterface
     */
    private $cache;

    /**
     * @var string
     */
    private $loadBalance;

    /**
     * @var LoadBalanceInterface[]
     */
    private $lb;

    public function __construct(ServiceResolverInterface $serviceResolver, CacheInterface $cache = null, string $loadBalance = null)
    {
        $this->serviceResolver = $serviceResolver;
        $this->cache = $cache ?? new InMemoryCache();
        $this->loadBalance = $loadBalance ?? LoadBalanceAlgorithm::ROUND_ROBIN;
    }

    public function process(RpcRequestInterface $request, RpcRequestHandlerInterface $handler): RpcResponseInterface
    {
        $host = $request->getUri()->getHost();
        $serviceLocator = $request->getRpcMethod()->getServiceLocator();
        if (Text::isNotEmpty($host) && $host !== $serviceLocator->getName()) {
            return $handler->handle($request);
        }
        $serviceEndpoint = $this->getServiceEndpoint($serviceLocator);
        $endpoint = $serviceEndpoint->getEndpoint($this->lb[(string) $serviceLocator]->select());
        Assert::notNull($endpoint);

        return $handler->handle($request->withUri(
            $request->getUri()->withHost($endpoint->getHost())
                ->withPort($endpoint->getPort())
        ));
    }

    public function removeAddress(ServiceLocator $serviceLocator, string $address): void
    {
        $serviceEndpoint = $this->getServiceEndpoint($serviceLocator);
        $serviceEndpoint = $serviceEndpoint->remove($serviceEndpoint->getEndpoint($address));
        $key = (string) $serviceLocator;
        $this->cache->set($key, $serviceEndpoint);
        $this->lb[$key] = $this->createLoadBalance($serviceEndpoint);
    }

    private function resolve(ServiceLocator $serviceLocator): ServiceEndpoint
    {
        $key = (string) $serviceLocator;
        $serviceEndpoint = $this->serviceResolver->resolve($serviceLocator);
        if (null === $serviceEndpoint || $serviceEndpoint->isEmpty()) {
            throw new \InvalidArgumentException("Cannot resolve service $key");
        }
        $this->cache->set($key, $serviceEndpoint);
        $this->lb[$key] = $this->createLoadBalance($serviceEndpoint);

        return $serviceEndpoint;
    }

    private function createLoadBalance(ServiceEndpoint $serviceEndpoint): LoadBalanceInterface
    {
        $endpoints = $serviceEndpoint->getEndpoints();
        $addresses = Arrays::pull($endpoints, 'address');
        $weights = array_map(static function (Endpoint $endpoint): int {
            return (int) ($endpoint->getOption('weight') ?? 100);
        }, $endpoints);
        switch ($this->loadBalance) {
            case LoadBalanceAlgorithm::ROUND_ROBIN:
                return new RoundRobin($addresses, $weights);
            case LoadBalanceAlgorithm::RANDOM:
                return new Random($addresses);
            default:
                return new Equality($addresses);
        }
    }

    private function getServiceEndpoint(ServiceLocator $serviceLocator): ServiceEndpoint
    {
        $key = (string) $serviceLocator;
        $serviceEndpoint = $this->cache->get($key);
        if (null === $serviceEndpoint) {
            $serviceEndpoint = $this->resolve($serviceLocator);
            $this->cache->set($key, $serviceEndpoint);
        }

        return $serviceEndpoint;
    }
}
