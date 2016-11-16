<?php
namespace kuiper\serializer;

use kuiper\annotations\ReaderInterface;
use kuiper\annotations\DocReaderInterface;
use kuiper\serializer\annotation\SerializeName;
use kuiper\serializer\annotation\SerializeIgnore;
use kuiper\serializer\exception\MalformedJsonException;
use kuiper\serializer\exception\TypeException;
use kuiper\serializer\exception\UnexpectedValueException;
use kuiper\serializer\exception\NotSerialableException;
use kuiper\reflection\ReflectionType;
use Psr\Log\LoggerInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use ReflectionClass;
use ReflectionMethod;
use ReflectionProperty;
use Exception;
use InvalidArgumentException;

class Serializer implements NormalizerInterface, JsonSerializerInterface, LoggerAwareInterface
{
    use LoggerAwareTrait;
    
    /**
     * Cached class metadata
     *
     * @var array
     */
    private static $METADATA;

    /**
     * @var ReaderInterface
     */
    private $annotationReader;

    /**
     * @var DocReaderInterface
     */
    private $docReader;
    
    public function __construct(ReaderInterface $reader, DocReaderInterface $docReader) {
        $this->annotationReader = $reader;
        $this->docReader = $docReader;
    }

    /**
     * @inheritDoc
     */
    public function toJson($object, $options = 0)
    {
        return json_encode($this->toArray($object), $options);
    }

    /**
     * @inheritDoc
     */
    public function fromJson($jsonString, $type)
    {
        return $this->fromArray(self::decodeJson($jsonString), $type);
    }

    /**
     * @inheritDoc
     */
    public function toArray($data)
    {
        if (is_array($data)) {
            $ret = [];
            foreach ($data as $key => $val) {
                $ret[$key] = $this->toArray($val);
            }
            return $ret;
        } elseif (is_object($data)) {
            return $this->objectToArray($data);
        } else {
            return $data;
        }
    }

    /**
     * @inheritDoc
     */
    public function fromArray(array $data, $type)
    {
        if ($type instanceof ReflectionType) {
            return $this->toType($data, $type);
        } elseif (is_string($type)) {
            return $this->toType($data, ReflectionType::parse($type));
        } elseif (is_object($type)) {
            return $this->arrayToObject($data, get_class($type), $type);
        } else {
            throw new InvalidArgumentException("Parameter type expects class name or object, got " . gettype($type));
        }
    }

    private function objectToArray($object)
    {
        $class = new ReflectionClass(get_class($object));
        $data = [];
        foreach ($this->getGetters($class) as $name => $getter) {
            try {
                $value = $this->fromType($this->getValue($object, $getter), $getter['type']);
            } catch (TypeException $e) {
                throw new TypeException("Cannot convert property '$name' to array: " . $e->getMessage());
            }
            if (isset($getter['serialize_name'])) {
                $name = $getter['serialize_name'];
            }
            $data[$name] = $value;
        }
        return $data;
    }

    private function arrayToObject(array $data, $className, $object = null)
    {
        $class = new ReflectionClass($className);
        if (!isset($object)) {
            $object = $class->newInstanceWithoutConstructor();
        }
        foreach ($this->getSetters($class) as $name => $setter) {
            if (isset($setter['serialize_name'])) {
                $name = $setter['serialize_name'];
            }
            if (!isset($data[$name])) {
                continue;
            }
            $value = $this->toType($data[$name], $setter['type']);
            $this->setValue($object, $setter, $value);
        }
        return $object;
    }

    private function getGetters(ReflectionClass $class)
    {
        $properties = $this->getClassMetadata($class);
        return $properties['getters'];
    }

    private function getSetters(ReflectionClass $class)
    {
        $properties = $this->getClassMetadata($class);
        return $properties['setters'];
    }

    /**
     * gets class properties metadata
     */
    private function getClassMetadata(ReflectionClass $class)
    {
        $className = $class->getName();
        if (isset(self::$METADATA[$className])) {
            return self::$METADATA[$className];
        }
        return self::$METADATA[$className] = $this->parseClassMetadata($class);
    }

    /**
     * @return array key is class property name, value is an array contains
     *  - name string name
     *  - serialize_name string serialize name
     *  - getter string method to get value
     *  - setter string method to set value
     *  - is_public boolean whether property is public
     *  - type ReflectionType
     */
    private function parseClassMetadata(ReflectionClass $class)
    {
        $this->logger && $this->logger->debug(
            "[Serializer] parse class metadata from " . $class->getName()
        );
        $isException = $class->isSubclassOf(Exception::class);
        $getters = [];
        $setters = [];
        foreach ($class->getMethods() as $method) {
            if ($method->isStatic() || !$method->isPublic()) {
                continue;
            }
            // ignore trace and traceAsString for Exception class
            if ($isException && in_array($method->getName(), ['getTrace', 'getTraceAsString'])) {
                continue;
            }
            $setter = $this->parseSetter($method);
            if ($setter) {
                $setters[$setter['name']] = $setter;
            }
            $getter = $this->parseGetter($method);
            if ($getter) {
                $getters[$getter['name']] = $getter;
            }
        }
        foreach ($class->getProperties() as $property) {
            if ($property->isStatic()) {
                continue;
            }
            $prop = $this->parseProperty($property);
            if ($prop === null) {
                continue;
            }
            $name = $prop['name'];
            if (isset($setters[$name])) {
                if ($setters[$name]['type']->isMixed()) {
                    $setters[$name]['type'] = $prop['type'];
                }
            } else {
                $setters[$name] = $prop;
            }
            if (isset($getters[$name])) {
                if ($getters[$name]['type']->isMixed()) {
                    $getters[$name]['type'] = $prop['type'];
                }
            } else {
                $getters[$name] = $prop;
            }
        }
        return ['getters' => $getters, 'setters' => $setters];
    }

    protected function parseSetter(ReflectionMethod $method)
    {
        $name = $method->getName();
        if (strpos($name, 'set') == 0
            && $method->getNumberOfParameters() === 1) {
            if ($this->isIgnore($method)) {
                return;
            }
            $types = array_values($this->docReader->getParameterTypes($method));
            if (!$this->validateType($types[0])) {
                throw new NotSerialableException(sprintf(
                    "Cannot serialize class %s for method %s",
                    $method->getDeclaringClass()->getName(),
                    $method->getName()
                ));
            }
            return [
                'name' => lcfirst(substr($name, 3)),
                'setter' => $name,
                'type' => $types[0],
                'serialize_name' => $this->getSerializeName($method)
            ];
        }
    }

    protected function parseGetter(ReflectionMethod $method)
    {
        $name = $method->getName();
        if (preg_match('/^(get|is)(.+)/', $name, $matches)
            && $method->getNumberOfParameters() === 0) {
            if ($this->isIgnore($method)) {
                return;
            }
            $type = $this->docReader->getReturnType($method);
            if (!$this->validateType($type)) {
                throw new NotSerialableException(sprintf(
                    "Cannot serialize class %s for method %s",
                    $method->getDeclaringClass()->getName(),
                    $method->getName()
                ));
            }
            return [
                'name' => lcfirst($matches[2]),
                'getter' => $name,
                'type' => $type,
                'serialize_name' => $this->getSerializeName($method)
            ];
        }
    }

    protected function parseProperty(ReflectionProperty $property)
    {
        if ($this->isIgnore($property)) {
            return;
        }
        $type = $this->docReader->getPropertyType($property);
        if (!$this->validateType($type)) {
            throw new NotSerialableException(sprintf(
                "Cannot serialize class %s for property %s",
                $property->getDeclaringClass()->getName(),
                $property->getName()
            ));
        }
        return [
            'name' => $property->getName(),
            'is_public' => $property->isPublic(),
            'type' => $type,
            'serialize_name' => $this->getSerializeName($property)
        ];
    }

    protected function isIgnore($reflection)
    {
        if ($reflection instanceof ReflectionMethod) {
            $annot = $this->annotationReader->getMethodAnnotation($reflection, SerializeIgnore::class);
        } else {
            $annot = $this->annotationReader->getPropertyAnnotation($reflection, SerializeIgnore::class);
        }
        return $annot !== null;
    }

    protected function getSerializeName($reflection)
    {
        if ($reflection instanceof ReflectionMethod) {
            $annot = $this->annotationReader->getMethodAnnotation($reflection, SerializeName::class);
        } else {
            $annot = $this->annotationReader->getPropertyAnnotation($reflection, SerializeName::class);
        }
        return $annot !== null ? $annot->value : null;
    }

    protected function getValue($object, $getter)
    {
        if (isset($getter['getter'])) {
            $value = call_user_func([$object, $getter['getter']]);
        } elseif ($getter['is_public']) {
            $name = $getter['name'];
            $value = $object->{$name};
        } else {
            $property = $class->getProperty($getter['name']);
            $property->setAccessible(true);
            $value = $property->getValue($object);
        }
        return $value;
    }

    protected function setValue($object, $setter, $value)
    {
        if (isset($setter['setter'])) {
            call_user_func([$object, $setter['setter']], $value);
        } elseif ($setter['is_public']) {
            $object->{$setter['name']} = $value;
        } else {
            $property = $class->getProperty($setter['name']);
            $property->setAccessible(true);
            $property->setValue($object, $value);
        }
    }

    private function validateType(ReflectionType $type)
    {
        if ($type->isObject() && !$type->getClassName()) {
            return false;
        }
        return true;
    }

    /**
     * Converts value to the type
     * 
     * @param mixed $value
     * @param ReflectionType $type
     * @return mixed
     */
    protected function toType($value, ReflectionType $type)
    {
        if (!isset($value)) {
            // check type nullable? 
            return;
        }
        if ($type->isObject()) {
            $className = $type->getClassName();
            if (!$className) {
                throw new InvalidArgumentException("Parameter type expects class name, got '$type'");
            }
            if (!class_exists($className)) {
                throw new InvalidArgumentException("class '$className' does not exist");
            }
            return $this->arrayToObject($value, $className);
        } elseif ($type->isArray()) {
            if (!is_array($value)) {
                throw new UnexpectedValueException("expects array, got " . ReflectionType::describe($value));
            }
            $ret = [];
            $valueType = $type->getArrayValueType();
            foreach ($value as $key => $item) {
                $ret[$key] = $this->toType($item, $valueType);
            }
            return $ret;
        } elseif ($type->isCompound()) {
            foreach ($type->getCompoundTypes() as $subtype) {
                if ($subtype->validate($value)) {
                    return $this->toType($value, $subtype);
                }
            }
            throw new UnexpectedValueException("expects '$type', got " . ReflectionType::describe($value));
        } elseif ($type->validate($value)) {
            return $type->sanitize($value);
        } else {
            throw new UnexpectedValueException("expects '$type', got " . ReflectionType::describe($value));
        }
    }

    /**
     * Extracts values according to the type
     * 
     * @param mixed $value
     * @param ReflectionType $type
     * @return mixed
     * @throws TypeException
     */
    private function fromType($value, ReflectionType $type)
    {
        if (!isset($value)) {
            // skip null value
            return;
        }
        if ($type->isObject()) {
            $className = $type->getClassName();
            if ($value instanceof $className) {
                return $this->objectToArray($value);
            } else {
                throw new TypeException("expects $className, got ", ReflectionType::describe($value));
            }
        } elseif ($type->isArray()) {
            if (!is_array($value)) {
                throw new TypeException("expects array, got " . ReflectionType::describe($value));
            }
            $ret = [];
            $valueType = $type->getArrayValueType();
            foreach ($value as $key => $item) {
                $ret[$key] = $this->fromType($item, $valueType);
            }
            return $ret;
        } elseif ($type->isCompound()) {
            foreach ($type->getCompoundTypes() as $subtype) {
                if ($subtype->validate($value)) {
                    return $this->fromType($value, $subtype);
                }
            }
            throw new TypeException("expects '$type', got " . ReflectionType::describe($value));
        } elseif ($type->validate($value)) {
            return $type->sanitize($value);
        } else {
            throw new TypeException("expects '$type', got " . ReflectionType::describe($value));
        }
    }
                                                                    
    public static function decodeJson($json)
    {
        $data = json_decode($json, true);
        if (!is_array($data)) {
            if ($data === false) {
                throw new MalformedJsonException(sprintf(
                    "%s, json string was '%s'",
                    json_last_error_msg(),
                    $json
                ));
            } else {
                throw new InvalidArgumentException("Json data expected an array, got " . ReflectionType::describe($data));
            }
        }
        return $data;
    }
}
