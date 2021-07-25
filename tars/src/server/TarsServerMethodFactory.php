<?php

declare(strict_types=1);

namespace kuiper\tars\server;

use kuiper\annotations\AnnotationReaderInterface;
use kuiper\rpc\RpcMethodInterface;
use kuiper\tars\annotation\TarsServant;
use kuiper\tars\core\TarsMethodFactory;
use kuiper\tars\core\TarsMethodInterface;
use kuiper\tars\stream\RequestPacket;
use kuiper\tars\stream\TarsConst;
use kuiper\tars\stream\TarsInputStream;
use kuiper\tars\type\MapType;

class TarsServerMethodFactory extends TarsMethodFactory
{
    /**
     * @var array
     */
    private $servants;
    /**
     * @var ServerProperties
     */
    private $serverProperties;

    public function __construct(ServerProperties $serverProperties, array $servants, ?AnnotationReaderInterface $annotationReader = null)
    {
        parent::__construct($annotationReader);
        $this->serverProperties = $serverProperties;
        $this->servants = $servants;
    }

    public function create($service, string $method, array $args): RpcMethodInterface
    {
        $serviceImpl = $this->servants[$service];
        /** @var TarsMethodInterface $rpcMethod */
        $rpcMethod = parent::create($serviceImpl, $method, []);

        return $rpcMethod->withArguments($this->resolveParams($rpcMethod, $args[0]));
    }

    protected function getTarsServantAnnotation(\ReflectionClass $reflectionClass): TarsServant
    {
        $annotation = parent::getTarsServantAnnotation($reflectionClass);
        if (false === strpos($annotation->value, '.')) {
            $annotation->value = $this->serverProperties->getServerName().'.'.$annotation->value;
        }

        return $annotation;
    }

    private function resolveParams(TarsMethodInterface $method, RequestPacket $packet): array
    {
        $is = new TarsInputStream($packet->sBuffer);
        $parameters = [];
        if (TarsConst::VERSION === $packet->iVersion) {
            $params = $is->readMap(0, true, MapType::byteArrayMap());
            foreach ($method->getParameters() as $i => $parameter) {
                if (isset($params[$parameter->getName()])) {
                    $is = new TarsInputStream($params[$parameter->getName()]);
                    $parameters[] = $is->read(0, true, $parameter->getType());
                } else {
                    $parameters[] = null;
                }
            }
        } else {
            foreach ($method->getParameters() as $i => $parameter) {
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
