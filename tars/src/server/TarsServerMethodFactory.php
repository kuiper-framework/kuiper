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

use InvalidArgumentException;
use kuiper\rpc\exception\InvalidMethodException;
use kuiper\rpc\exception\ServiceNotFoundException;
use kuiper\rpc\RpcMethodInterface;
use kuiper\tars\core\TarsMethod;
use kuiper\tars\core\TarsMethodFactory;
use kuiper\tars\exception\SyntaxErrorException;
use kuiper\tars\exception\TarsStreamException;
use kuiper\tars\stream\RequestPacket;
use kuiper\tars\stream\TarsConst;
use kuiper\tars\stream\TarsInputStream;
use kuiper\tars\type\MapType;
use ReflectionClass;
use ReflectionException;

class TarsServerMethodFactory extends TarsMethodFactory
{
    public function __construct(private readonly string $serverName, private readonly array $services)
    {
        parent::__construct();
    }

    /**
     * {@inheritDoc}
     */
    public function create(object|string $service, string $method, array $args): RpcMethodInterface
    {
        if (is_object($service)) {
            try {
                $tarsServant = $this->getTarsServantAnnotation(new ReflectionClass($service));
            } catch (ReflectionException $e) {
                throw new InvalidMethodException("Cannot resolve method " . get_class($service) . "::$method", 0, $e);
            }
            if (!str_contains($tarsServant->getService(), '.')) {
                $serviceName = $this->serverName.'.'.$tarsServant->getService();
            } else {
                $serviceName = $tarsServant->getService();
            }
        } else {
            $serviceName = $service;
        }
        if (!isset($this->services[$serviceName])) {
            throw new ServiceNotFoundException("Cannot find tars servant $serviceName");
        }
        $serviceObject = $this->services[$serviceName];
        $serviceImpl = $serviceObject->getService();
        try {
            [$parameters, $returnValue] = $this->getParameters($serviceImpl, $method);
        } catch (ReflectionException|SyntaxErrorException $e) {
            throw new InvalidMethodException("Cannot resolve method parameters", 0, $e);
        }

        try {
            $arguments = $this->resolveParams($parameters, $args[0]);
        } catch (TarsStreamException $e) {
            throw new InvalidArgumentException("Unmarshal method parameters failed", 0, $e);
        }
        return new TarsMethod($serviceImpl, $serviceName, $method, $arguments, $parameters, $returnValue);
    }

    /**
     * @throws TarsStreamException
     */
    private function resolveParams(array $methodParameters, RequestPacket $packet): array
    {
        $is = new TarsInputStream($packet->sBuffer);
        $parameters = [];
        if (TarsConst::VERSION === $packet->iVersion) {
            $params = $is->readMap(0, true, MapType::byteArrayMap());
            foreach ($methodParameters as $parameter) {
                if (isset($params[$parameter->getName()])) {
                    $is = new TarsInputStream($params[$parameter->getName()]);
                    $parameters[] = $is->read(0, true, $parameter->getType());
                } else {
                    $parameters[] = null;
                }
            }
        } else {
            foreach ($methodParameters as $parameter) {
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
