<?php
namespace kuiper\rpc\server;

use InvalidArgumentException;
use UnexpectedValueException;
use kuiper\serializer\exception\SerializeException;
use kuiper\serializer\NormalizerInterface;
use kuiper\reflection\ReflectionType;

class Method
{
    /**
     * @var string
     */
    private $name;
    
    /**
     * @var callable
     */
    private $callable;

    /**
     * @var ReflectionType[]
     */
    private $paramTypes;

    /**
     * @var string[]
     */
    private $paramNames;

    /**
     * @var int[]
     */
    private $paramIndexes;

    /**
     * @var NormalizerInterface
     */
    private $normalizer;
    
    public function __construct($name, $callable, NormalizerInterface $normalizer, array $paramTypes, array $paramNames)
    {
        $this->name = $name;
        $this->callable = $callable;
        $this->normalizer = $normalizer;
        $this->paramTypes = $paramTypes;
        $this->paramNames = $paramNames;
        $this->paramIndexes = array_flip($paramNames);
    }

    /**
     * Invokes method
     * 
     * @param array $params
     * @return mixed
     * @throws InvalidArgumentException
     */
    public function call(array $params = [])
    {
        if (count($params) > ($nofParams = count($this->paramTypes))) {
            throw new InvalidArgumentException(sprintf(
                "Parameters count not match, expects %d params, got %d", $nofParams, count($params)
            ));
        }
        $args = [];
        foreach ($params as $i => $value) {
            if (isset($this->paramNames[$i])) {
                $i = $this->paramNames[$i];
            }
            if (!array_key_exists($i, $this->paramTypes)) {
                throw new InvalidArgumentException("Unknown parameter '$i'");
            }
            $type = $this->paramTypes[$i];
            if (is_array($value)) {
                try {
                    $value = $this->normalizer->fromArray($value, $type);
                } catch (SerializeException $e) {
                    throw new InvalidArgumentException($e->getMessage());
                }
            } elseif ($type->validate($value)) {
                $value = $type->sanitize($value);
            } else {
                throw new InvalidArgumentException("parameter $i of method {$this->name} expects '{$type}', got " . ReflectionType::describe($value));
            }
            $args[$this->paramIndexes[$i]] = $value;
        }
        ksort($args, SORT_NUMERIC);
        return call_user_func_array($this->callable, $args);
    }
}
