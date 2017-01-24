<?php
namespace kuiper\rpc\server;

use ReflectionMethod;
use kuiper\annotations\DocReaderInterface;
use kuiper\serializer\NormalizerInterface;

class MethodFactory
{
    /**
     * @var NormalizerInterface
     */
    private $normalizer;

    /**
     * @var DocReaderInterface
     */
    private $docReader;
    
    public function __construct(NormalizerInterface $normalizer, DocReaderInterface $docReader)
    {
        $this->normalizer = $normalizer;
        $this->docReader = $docReader;
    }
    
    public function create($service, ReflectionMethod $method)
    {
        $paramTypes = $this->docReader->getParameterTypes($method);
        $paramNames = [];
        foreach ($method->getParameters() as $i => $parameter) {
            $paramNames[$i] = $parameter->getName();
        }
        return new Method(
            $name = get_class($service) . '::' . $method->getName(),
            $callable = [$service, $method->getName()],
            $this->normalizer,
            $paramTypes,
            $paramNames
        );
    }
}