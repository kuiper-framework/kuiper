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

use kuiper\annotations\AnnotationReaderInterface;
use kuiper\helper\Text;
use kuiper\jsonrpc\annotation\JsonRpcClient;
use kuiper\jsonrpc\core\JsonRpcProtocol;
use kuiper\rpc\client\ProxyGenerator;
use kuiper\rpc\RpcMethod;
use kuiper\rpc\RpcMethodFactoryInterface;
use kuiper\rpc\RpcMethodInterface;
use kuiper\rpc\ServiceLocator;

class JsonRpcMethodFactory implements RpcMethodFactoryInterface
{
    /**
     * @var AnnotationReaderInterface
     */
    private $annotationReader;

    /**
     * @var array
     */
    private $options;

    /**
     * @var ServiceLocator[]
     */
    private $serviceLocators;

    /**
     * JsonRpcMethodFactory constructor.
     *
     * @param AnnotationReaderInterface $annotationReader
     */
    public function __construct(AnnotationReaderInterface $annotationReader, array $options = [])
    {
        $this->annotationReader = $annotationReader;
        $this->options = $options;
    }

    /**
     * {@inheritDoc}
     */
    public function create($service, string $method, array $args): RpcMethodInterface
    {
        $className = is_string($service) ? $service : get_class($service);
        if (!isset($this->serviceLocators[$className])) {
            $interfaceName = ProxyGenerator::getInterfaceName($className);
            if (null === $interfaceName) {
                throw new \InvalidArgumentException("Cannot find interface class for {$className}");
            }
            $options = $this->options[$interfaceName] ?? [];
            /** @var JsonRpcClient|null $annotation */
            $annotation = $this->annotationReader->getClassAnnotation(new \ReflectionClass($interfaceName), JsonRpcClient::class);
            if (!isset($options['service'])) {
                $options['service'] = (null !== $annotation) && Text::isNotEmpty($annotation->service)
                    ? $annotation->service
                    : str_replace('\\', '.', $interfaceName);
            }
            if (!isset($options['version'])) {
                $options['version'] = (null !== $annotation) && Text::isNotEmpty($annotation->version)
                    ? $annotation->version
                    : '1.0';
            }
            $this->serviceLocators[$className] = new ServiceLocator($options['service'], JsonRpcProtocol::NS, $options['version']);
        }

        return new RpcMethod($service, $this->serviceLocators[$className], $method, $args);
    }
}
