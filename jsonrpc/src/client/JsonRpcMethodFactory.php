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

namespace kuiper\jsonrpc\client;

use kuiper\helper\Text;
use kuiper\jsonrpc\attribute\JsonRpcClient;
use kuiper\jsonrpc\core\JsonRpcProtocol;
use kuiper\rpc\client\ProxyGenerator;
use kuiper\rpc\RpcMethod;
use kuiper\rpc\RpcMethodFactoryInterface;
use kuiper\rpc\RpcMethodInterface;
use kuiper\rpc\ServiceLocator;
use kuiper\rpc\ServiceLocatorImpl;

class JsonRpcMethodFactory implements RpcMethodFactoryInterface
{
    /**
     * @var ServiceLocator[]
     */
    private array $serviceLocators;

    public function __construct(private readonly array $options = [])
    {
    }

    /**
     * {@inheritDoc}
     */
    public function create(object|string $service, string $method, array $args): RpcMethodInterface
    {
        $className = is_string($service) ? $service : get_class($service);
        if (!isset($this->serviceLocators[$className])) {
            $interfaceName = ProxyGenerator::getInterfaceName($className);
            if (null === $interfaceName) {
                throw new \InvalidArgumentException("Cannot find interface class for {$className}");
            }
            $options = $this->options[$interfaceName] ?? [];

            $reflectionClass = new \ReflectionClass($interfaceName);
            $attributes = $reflectionClass->getAttributes(JsonRpcClient::class);
            /** @var JsonRpcClient|null $attribute */
            $attribute = null;
            if (count($attributes) > 0) {
                $attribute = $attributes[0]->newInstance();
            }
            if (!isset($options['service'])) {
                $options['service'] = (null !== $attribute) && $attribute->getService() !== ''
                    ? $attribute->getService()
                    : str_replace('\\', '.', $interfaceName);
            }
            if (!isset($options['version'])) {
                $options['version'] = (null !== $attribute) && $attribute->getVersion() !== ''
                    ? $attribute->getVersion()
                    : '1.0';
            }
            $this->serviceLocators[$className] = new ServiceLocatorImpl($options['service'], JsonRpcProtocol::NS, $options['version']);
        }

        return new RpcMethod($service, $this->serviceLocators[$className], $method, $args);
    }
}
