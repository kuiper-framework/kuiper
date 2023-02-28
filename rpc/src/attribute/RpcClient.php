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

namespace kuiper\rpc\attribute;

use Attribute;
use kuiper\di\attribute\ComponentTrait;
use kuiper\di\Component;

#[Attribute(Attribute::TARGET_CLASS)]
class RpcClient implements Component
{
    use ComponentTrait;

    public function __construct(
        private readonly string $service = '',
        private readonly string $version = '',
        private readonly string $ns = '',
        private readonly string $protocol = '',
        private readonly string $endpoint = '')
    {
    }

    /**
     * @return string
     */
    public function getService(): string
    {
        return $this->service;
    }

    /**
     * @return string
     */
    public function getVersion(): string
    {
        return $this->version;
    }

    /**
     * @return string
     */
    public function getNamespace(): string
    {
        return $this->ns;
    }

    /**
     * @return string
     */
    public function getProtocol(): string
    {
        return $this->protocol;
    }

    /**
     * @return string
     */
    public function getEndpoint(): string
    {
        return $this->endpoint;
    }

    public function toArray(): array
    {
        return [
            'service' => $this->service,
            'version' => $this->version,
            'namespace' => $this->ns,
            'protocol' => $this->protocol,
            'endpoint' => $this->endpoint,
        ];
    }
}
