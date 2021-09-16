<?php

/*
 * This file is part of the Kuiper package.
 *
 * (c) Ye Wenbin <wenbinye@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace kuiper\rpc\server\listener;

use kuiper\event\EventSubscriberInterface;
use kuiper\rpc\server\Service;
use kuiper\rpc\server\ServiceRegistryInterface;
use kuiper\swoole\event\ShutdownEvent;
use kuiper\swoole\event\WorkerStartEvent;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;

class ServiceDiscoveryListener implements EventSubscriberInterface, LoggerAwareInterface
{
    use LoggerAwareTrait;

    /**
     * @var ServiceRegistryInterface
     */
    private $serviceRegistry;

    /**
     * @var Service[]
     */
    private $services;

    /**
     * ServiceDiscoveryListener constructor.
     *
     * @param ServiceRegistryInterface $serviceRegistry
     * @param Service[]                $services
     */
    public function __construct(ServiceRegistryInterface $serviceRegistry, array $services)
    {
        $this->serviceRegistry = $serviceRegistry;
        $this->services = $services;
    }

    public function __invoke($event): void
    {
        if ($event instanceof WorkerStartEvent) {
            if (0 === $event->getWorkerId()) {
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
