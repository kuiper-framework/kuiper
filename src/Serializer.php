<?php
namespace kuiper\serializer;

use kuiper\annotations\ReaderInterface;
use kuiper\annotations\DocReader;
use kuiper\serializer\annotation\SerializeName;
use kuiper\serializer\annotation\SerializeIgnore;
use kuiper\serializer\expection\MalformedJsonException;
use kuiper\serializer\expection\TypeException;
use kuiper\reflection\VarType;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Log\LoggerInterface;
use InvalidArgumentException;
use ReflectionClass;
use ReflectionMethod;
use ReflectionProperty;
use Exception;

class Serializer implements ArraySerializerInterface, JsonSerializerInterface
{
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
     * @var CacheItemPoolInterface
     */
    private $cache;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var DocReader
     */
    private $docReader;
    
    public function __construct(
        ReaderInterface $reader,
        LoggerInterface $logger = null,
        CacheItemPoolInterface $cache = null
    ) {
        $this->annotationReader = $reader;
        $this->logger = $logger;
        $this->cache = $cache;
        $this->docReader = new DocReader();
    }

    /**
     * Serializes data into json
     *
     * @param object $obj
     * @return string
     */
    public function toJson($object)
    {
        return json_encode($this->toArray($object));
    }

    /**
     * Deserializes json to object
     *
     * @param string $jsonString
     * @param string $className
     * @return object
     */
    public function fromJson($jsonString, $className)
    {
        return $this->fromArray(self::decodeJson($jsonString), $className);
    }

    /**
     * Normalizes the object into an array of scalars|arrays.
     *
     * @param object $object
     * @return array
     */
    public function toArray($object)
    {
        if ($object === null || !is_object($object)) {
            throw new InvalidArgumentException("Expect object type, got " . VarType::describe($object));
        }
        $class = new ReflectionClass(get_class($object));
        $data = [];
        foreach ($this->getGetters($class) as $name => $getter) {
            $value = $this->getValue($object, $getter);
            $type = $getter['type'];
            if ($type->isObjectType()) {
                // ignore null value
                if (isset($value)) {
                    if (is_a($value, $type->getType(), true)) {
                        $value = $this->toArray($value);
                    } else {
                        throw new TypeException(sprintf(
                            "Property '%s' expects %s, got %s",
                            $name,
                            $type->getType(),
                            VarType::describe($value)
                        ));
                    }
                }
            } elseif (is_array($value)
                      && $type->isArray()
                      && $type->getType()->isObjectType()) {
                $propertyData = [];
                foreach ($value as $key => $item) {
                    $propertyData[$key] = $this->toArray($item);
                }
                $value = $propertyData;
            } else {
                $value = $type->sanitize($value);
            }
            if (isset($getter['serialize_name'])) {
                $name = $getter['serialize_name'];
            }
            $data[$name] = $value;
        }
        return $data;
    }

    /**
     * Denormalizes data back into an object of the given class.
     *
     * @param array $data
     * @param string $className
     * @return object
     */
    public function fromArray(array $data, $className)
    {
        $class = new ReflectionClass($className);
        if (is_string($className)) {
            $object = $class->newInstanceWithoutConstructor();
        } else {
            $object = $className;
            $className = get_class($object);
        }
        foreach ($this->getSetters($class) as $name => $setter) {
            if (isset($setter['serialize_name'])) {
                $name = $setter['serialize_name'];
            }
            if (!isset($data[$name])) {
                continue;
            }
            $value = $data[$name];
            $type = $setter['type'];
            if ($type->isObjectType()) {
                $value = $this->fromArray($value, $type->getType());
            } elseif ($type->isArray()) {
                if ($type->getType()->isObjectType()) {
                    $itemClass = $type->getType()->getType();
                    $propertyData = [];
                    foreach ($value as $key => $item) {
                        $propertyData[$key] = $this->fromArray($item, $itemClass);
                    }
                    $value = $propertyData;
                } else {
                    $value = $type->sanitize($value);
                }
            } else {
                $value = $type->sanitize($value);
            }
            if (!$type->validate($value)) {
                throw new InvalidArgumentException(sprintf(
                    "Property %s of %s expects a %s, got %s",
                    $name,
                    $className,
                    $type,
                    VarType::describe($value)
                ));
            }
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
        if (isset($this->cache)) {
            $item = $this->cache->getItem('serializer\properties:' . $className);
            if (!$item->isHit()) {
                $this->cache->save($item->set($this->parseClassMetadata($class)));
            }
            return self::$METADATA[$className] = $item->get();
        } else {
            return self::$METADATA[$className] = $this->parseClassMetadata($class);
        }
    }

    /**
     * @return array key is class property name, value is an array contains
     *  - name string name
     *  - serialize_name string serialize name
     *  - getter string method to get value
     *  - setter string method to set value
     *  - is_public boolean whether property is public
     *  - type VarType
     */
    private function parseClassMetadata(ReflectionClass $class)
    {
        isset($this->logger) && $this->logger->debug(
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
                if ($setters[$name]['type']->isUnknown()) {
                    $setters[$name]['type'] = $prop['type'];
                }
            } else {
                $setters[$name] = $prop;
            }
            if (isset($getters[$name])) {
                if ($getters[$name]['type']->isUnknown()) {
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
            $parameters = $method->getParameters();
            $parameter = $parameters[0];
            if (($paramClass = $parameter->getClass()) !== null) {
                $type = VarType::objectType($paramClass);
            } else {
                $params = $this->docReader->getParameterTypes($method);
                $type = isset($params[$parameter->getName()])
                      ? $params[$parameter->getName()]
                      : VarType::mixed();
            }
            return [
                'name' => lcfirst(substr($name, 3)),
                'setter' => $name,
                'type' => $type,
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
            
            return [
                'name' => lcfirst($matches[2]),
                'getter' => $name,
                'type' => $this->docReader->getReturnType($method),
                'serialize_name' => $this->getSerializeName($method)
            ];
        }
    }

    protected function parseProperty(ReflectionProperty $property)
    {
        if ($this->isIgnore($property)) {
            return;
        }
        return [
            'name' => $property->getName(),
            'is_public' => $property->isPublic(),
            'type' => $this->docReader->getPropertyType($property),
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
                throw new InvalidArgumentException("Json data expected an array, got " . VarType::describe($data));
            }
        }
        return $data;
    }

    public function setAnnotationReader(ReaderInterface $annotationReader)
    {
        $this->annotationReader = $annotationReader;
        return $this;
    }

    public function setCache(CacheItemPoolInterface $cache)
    {
        $this->cache = $cache;
        return $this;
    }

    public function setLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;
        return $this;
    }
}
