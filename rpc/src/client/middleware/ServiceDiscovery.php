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

    /**
     * ServiceDiscovery constructor.
     *
     * @param ServiceResolverInterface $serviceResolver
     */
    public function __construct(ServiceResolverInterface $serviceResolver, CacheInterface $cache = null, string $loadBalance = LoadBalanceAlgorithm::ROUND_ROBIN)
    {
        $this->serviceResolver = $serviceResolver;
        $this->cache = $cache ?? new InMemoryCache();
        $this->loadBalance = $loadBalance;
    }

    public function process(RpcRequestInterface $request, RpcRequestHandlerInterface $handler): RpcResponseInterface
    {
        $host = $request->getUri()->getHost();
        if (Text::isNotEmpty($host) && $host !== $request->getRpcMethod()->getServiceLocator()->getName()) {
            return $handler->handle($request);
        }
        $key = (string) $request->getRpcMethod()->getServiceLocator();
        $serviceEndpoint = $this->cache->get($key);
        if (null === $serviceEndpoint) {
            $serviceEndpoint = $this->resolve($request->getRpcMethod()->getServiceLocator());
        }
        if (null === $serviceEndpoint || $serviceEndpoint->isEmpty()) {
            throw new \InvalidArgumentException("Cannot resolve service $key");
        }
        $endpoint = $serviceEndpoint->getEndpoint($this->lb[$key]->select());
        Assert::notNull($endpoint);

        return $handler->handle($request->withUri(
            $request->getUri()->withHost($endpoint->getHost())
                ->withPort($endpoint->getPort())
        ));
    }

    private function resolve(ServiceLocator $serviceLocator): ServiceEndpoint
    {
        $key = (string) $serviceLocator;
        $serviceEndpoint = $this->serviceResolver->resolve($serviceLocator);
        if (null === $serviceEndpoint) {
            throw new \InvalidArgumentException("Cannot resolve service $key");
        }
        $this->cache->set($key, $serviceEndpoint);
        $endpoints = $serviceEndpoint->getEndpoints();
        $addresses = Arrays::pull($endpoints, 'address');
        $weights = array_map(function (Endpoint $endpoint) {
            return (int) ($endpoint->getOption('weight') ?? 100);
        }, $endpoints);
        switch ($this->loadBalance) {
            case LoadBalanceAlgorithm::ROUND_ROBIN:
                $this->lb[$key] = new RoundRobin($addresses, $weights);
                break;
            case LoadBalanceAlgorithm::RANDOM:
                $this->lb[$key] = new Random($addresses);
                break;
            case LoadBalanceAlgorithm::EQUALITY:
                $this->lb[$key] = new Equality($addresses);
                break;
        }

        return $serviceEndpoint;
    }
}
