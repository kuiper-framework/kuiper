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

namespace kuiper\rpc\registry\consul;

use kuiper\http\client\attribute\GetMapping;
use kuiper\http\client\attribute\HttpClient;
use kuiper\http\client\attribute\HttpHeader;
use kuiper\http\client\attribute\PutMapping;
use kuiper\http\client\attribute\QueryParam;

#[HttpClient(path: '/v1/agent')]
#[HttpHeader('content-type', 'application/json')]
interface ConsulAgent
{
    /**
     * @return Service[]
     */
    #[GetMapping('/services')]
    public function getServices(string $filter = null, string $ns = null): array;

    /**
     * Gets the service.
     */
    #[GetMapping('/service/{service}')]
    public function getService(string $service): Service;

    #[PutMapping('/service/register')]
    public function registerService(
        RegisterServiceRequest $request,
        #[QueryParam('replace-existing-checks')] bool $replaceExistingChecks = null): void;
    #[PutMapping('/service/deregister/{service}')]
    public function deregisterService(string $service): void;

    /**
     * @param string $service
     *
     * @return ServiceHealth[]
     */
    #[GetMapping('/health/service/name/{service}')]
    public function getServiceHealth(string $service): array;
}
