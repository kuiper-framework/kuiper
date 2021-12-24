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

namespace kuiper\tars\server;

use kuiper\annotations\AnnotationReaderInterface;
use kuiper\rpc\exception\ServiceNotFoundException;
use kuiper\rpc\RpcMethodInterface;
use kuiper\rpc\server\Service;
use kuiper\tars\core\TarsMethod;
use kuiper\tars\core\TarsMethodFactory;
use kuiper\tars\stream\RequestPacket;
use kuiper\tars\stream\TarsConst;
use kuiper\tars\stream\TarsInputStream;
use kuiper\tars\type\MapType;

class TarsServerMethodFactory extends TarsMethodFactory
{
    /**
     * @var Service[]
     */
    private $services;
    /**
     * @var string
     */
    private $serverName;

    /**
     * TarsServerMethodFactory constructor.
     *
     * @param string                    $serverName
     * @param Service[]                 $services
     * @param AnnotationReaderInterface $annotationReader
     */
    public function __construct(string $serverName, array $services, AnnotationReaderInterface $annotationReader)
    {
        parent::__construct($annotationReader);
        $this->serverName = $serverName;
        $this->services = $services;
    }

    /**
     * {@inheritDoc}
     */
    public function create($service, string $method, array $args): RpcMethodInterface
    {
        if (is_object($service)) {
            $tarsServant = $this->getTarsServantAnnotation(new \ReflectionClass($service));
            if (false === strpos($tarsServant->service, '.')) {
                $serviceName = $this->serverName.'.'.$tarsServant->service;
            } else {
                $serviceName = $tarsServant->service;
            }
        } else {
            $serviceName = $service;
        }
        if (!isset($this->services[$serviceName])) {
            throw new ServiceNotFoundException("Cannot find tars servant $serviceName");
        }
        $serviceObject = $this->services[$serviceName];
        $serviceImpl = $serviceObject->getService();
        [$parameters, $returnValue] = $this->getParameters($serviceImpl, $method);

        return new TarsMethod($serviceImpl, $serviceName, $method, $this->resolveParams($parameters, $args[0]), $parameters, $returnValue);
    }

    private function resolveParams(array $methodParameters, RequestPacket $packet): array
    {
        $is = new TarsInputStream($packet->sBuffer);
        $parameters = [];
        if (TarsConst::VERSION === $packet->iVersion) {
            $params = $is->readMap(0, true, MapType::byteArrayMap());
            foreach ($methodParameters as $i => $parameter) {
                if (isset($params[$parameter->getName()])) {
                    $is = new TarsInputStream($params[$parameter->getName()]);
                    $parameters[] = $is->read(0, true, $parameter->getType());
                } else {
                    $parameters[] = null;
                }
            }
        } else {
            foreach ($methodParameters as $i => $parameter) {
                if ($parameter->isOut()) {
                    $parameters[] = null;
                } else {
                    $parameters[] = $is->read(0, true, $parameter->getType());
                }
            }
        }

        return $parameters;
    }
}
