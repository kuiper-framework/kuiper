<?php

declare(strict_types=1);

namespace kuiper\rpc\server\listener;

use kuiper\event\EventSubscriberInterface;
use kuiper\rpc\server\Service;
use kuiper\rpc\server\ServiceRegistry;
use kuiper\swoole\event\ShutdownEvent;
use kuiper\swoole\event\WorkerStartEvent;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;

class ServiceDiscoveryListener implements EventSubscriberInterface, LoggerAwareInterface
{
    use LoggerAwareTrait;

    /**
     * @var ServiceRegistry
     */
    private $serviceRegistry;

    /**
     * @var Service[]
     */
    private $services;

    /**
     * ServiceDiscoveryListener constructor.
     *
     * @param ServiceRegistry $serviceRegistry
     * @param Service[]       $services
     */
    public function __construct(ServiceRegistry $serviceRegistry, array $services)
    {
        $this->serviceRegistry = $serviceRegistry;
        $this->services = $services;
    }

    public function __invoke($event): void
    {
        if ($event instanceof WorkerStartEvent) {
            if ($event->getServer()->isTaskWorker()) {
                foreach ($this->services as $service) {
                    $this->serviceRegistry->register($service);
                }
            }
        } elseif ($event instanceof ShutdownEvent) {
            foreach ($this->services as $service) {
                $this->serviceRegistry->deregister($service);
            }
        }
    }

    public function getSubscribedEvents(): array
    {
        return [
            WorkerStartEvent::class,
            ShutdownEvent::class,
        ];
    }
}
