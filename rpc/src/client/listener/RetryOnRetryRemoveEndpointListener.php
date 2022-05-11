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

namespace kuiper\rpc\client\listener;

use kuiper\event\EventListenerInterface;
use kuiper\resilience\retry\event\RetryOnRetry;
use kuiper\rpc\client\middleware\ServiceDiscovery;
use kuiper\rpc\client\RpcRequestHolder;
use kuiper\rpc\exception\ConnectionException;
use kuiper\rpc\RpcRequestInterface;
use kuiper\rpc\transporter\AbstractTcpTransporter;

class RetryOnRetryRemoveEndpointListener implements EventListenerInterface
{
    public function __construct(private readonly ServiceDiscovery $serviceDiscovery)
    {
    }

    public function __invoke(object $event): void
    {
        if (!$event instanceof RetryOnRetry) {
            return;
        }
        $error = $event->getLastException();
        if (!$error instanceof ConnectionException) {
            return;
        }
        $connection = $error->getConnection();
        if (!$connection instanceof AbstractTcpTransporter) {
            return;
        }
        $request = RpcRequestHolder::peek();
        if (!$request instanceof RpcRequestInterface) {
            return;
        }
        $this->serviceDiscovery->removeAddress($request->getRpcMethod()->getServiceLocator(),
            $connection->getEndpoint()->getAddress());
    }

    public function getSubscribedEvent(): string
    {
        return RetryOnRetry::class;
    }
}
