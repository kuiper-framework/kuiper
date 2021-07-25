<?php

declare(strict_types=1);

namespace kuiper\rpc\transporter;

use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

class LoadBalanceHolder implements EndpointHolderInterface, Refreshable, LoggerAwareInterface
{
    use LoggerAwareTrait;
    protected const TAG = '['.__CLASS__.'] ';

    /**
     * @var ServiceResolverInterface
     */
    private $serviceResolver;
    /**
     * @var string
     */
    private $service;
    /**
     * @var string
     */
    private $loadBalanceType;
    /**
     * @var LoadBalanceInterface|null
     */
    private $loadBalance;
    /**
     * @var Endpoint|null
     */
    private $endpoint;

    /**
     * LoadBalanceRouteHolder constructor.
     */
    public function __construct(ServiceResolverInterface $serviceResolver, string $service, string $loadBalance, ?LoggerInterface $logger = null)
    {
        $this->serviceResolver = $serviceResolver;
        $this->service = $service;
        $this->setLoadBalance($loadBalance);
        $this->setLogger($logger ?? new NullLogger());
    }

    public function get(): Endpoint
    {
        if (!isset($this->endpoint)) {
            try {
                $route = $this->serviceResolver->resolve($this->service);
            } catch (\Exception $e) {
                $this->logger->error(static::TAG."Resolve {$this->service} failed: ".get_class($e).': '.$e->getMessage());
                throw new \InvalidArgumentException('Cannot resolve route for servant '.$this->service, 0, $e);
            }
            if (null === $route) {
                throw new \InvalidArgumentException('Cannot resolve route for servant '.$this->service);
            }
            $addresses = $route->getEndpoints();
            if (empty($addresses)) {
                throw new \InvalidArgumentException("Servant {$this->service} address list is empty");
            }
            // 随机排序
            shuffle($addresses);
            $this->loadBalance = $this->createLoadBalance($addresses, array_map(static function (Endpoint $endpoint) use ($route): int {
                return $route->getWeight($endpoint->getAddress());
            }, $addresses));
            $this->endpoint = $this->loadBalance->select();
        }

        return $this->endpoint;
    }

    public function refresh(bool $force = false): void
    {
        if ($force) {
            $this->endpoint = null;
            $this->loadBalance = null;
        } elseif (isset($this->loadBalance)) {
            $this->endpoint = $this->loadBalance->select();
        }
    }

    private function createLoadBalance(array $addresses, array $weights): LoadBalanceInterface
    {
        $className = $this->loadBalanceType;

        return new $className($addresses, $weights);
    }

    public function setLoadBalance(string $lb): void
    {
        if (LoadBalanceAlgorithm::hasValue($lb)) {
            $className = LoadBalanceAlgorithm::fromValue($lb)->implementation;
        } else {
            $className = $lb;
        }
        if (!class_exists($className)) {
            throw new \InvalidArgumentException('unknown load balance type '.$lb);
        }
        if (!is_a($className, LoadBalanceInterface::class, true)) {
            throw new \InvalidArgumentException("Load balance type $className should implements ".LoadBalanceInterface::class);
        }

        $this->loadBalanceType = $className;
    }
}
