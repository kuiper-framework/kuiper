<?php
namespace kuiper\annotations;

use ReflectionProperty;
use ReflectionMethod;
use ReflectionClass;
use InvalidArgumentException;
use kuiper\reflection\ReflectionFile;
use kuiper\reflection\VarType;
use kuiper\annotations\exception\ClassNotFoundException;

class DocReader
{
    public function __construct()
    {
        DocParser::checkDocReadability();
    }

    /**
     * Parse the docblock of the property to get the class of the var annotation.
     *
     * @param ReflectionProperty $property
     *
     * @throws ClassNotFoundException
     * @return VarType Type of the property
     */
    public function getPropertyType(ReflectionProperty $property)
    {
        return $this->parseAnnotationType(
            $property->getDocComment(),
            $property->getDeclaringClass(),
            'var'
        );
    }

    /**
     * Parse the docblock of the property to get the class of the var annotation.
     *
     * @param ReflectionProperty $property
     *
     * @throws ClassNotFoundException
     * @return string|null Type of the property (content of var annotation)
     */
    public function getPropertyClass(ReflectionProperty $property)
    {
        return $this->getClassType($this->getPropertyType($property));
    }

    /**
     * Parses the docblock of the method to get all parameters type
     *
     * @param ReflectionMethod $method
     * @return array
     */
    public function getParameterTypes(ReflectionMethod $method)
    {
        $parameters = [];
        foreach ($method->getParameters() as $parameter) {
            if (method_exists($parameter, 'hasType')
                && $parameter->hasType()) {
                $parameters[$parameter->getName()]
                    = VarType::fromReflectionType($parameter->getType());
            } else {
                $parameters[$parameter->getName()] = VarType::mixed();
            }
        }
        $re = '/@param\s+(\S+)\s+\$([a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*)/';
        if (!preg_match_all($re, $this->getMethodDocComment($method), $matches)) {
            return $parameters;
        }
        $declaringClass = $method->getDeclaringClass();
        foreach ($matches[2] as $index => $name) {
            if (!isset($parameters[$name])) {
                continue;
            }
            if ($parameters[$name]->isUnknown()) {
                // if type is unknown
                $type = $this->parseType($matches[1][$index], $declaringClass);
                if (!$type->isMixed()) {
                    $parameters[$name] = $type;
                }
            }
        }
        return $parameters;
    }

    /**
     * Parses the docblock of the method to get all class type of parameters
     *
     * @param ReflectionMethod $method
     * @return array
     */
    public function getParameterClasses(ReflectionMethod $method)
    {
        $parameters = [];
        foreach ($this->getParameterTypes($method) as $name => $type) {
            $parameters[$name] = $this->getClassType($type);
        }
        return array_filter($parameters);
    }

    /**
     * Parses the docblock of the method to get return type
     *
     * @param ReflectionMethod $method
     * @return array|null
     */
    public function getReturnType(ReflectionMethod $method)
    {
        if (method_exists($method, 'hasReturnType')
            && $method->hasReturnType()) {
            $type = VarType::fromReflectionType($method->getReturnType());
            if (!$type->isUnknown()) {
                return $type;
            }
        }
        $annotType = $this->parseAnnotationType(
            $this->getMethodDocComment($method),
            $method->getDeclaringClass(),
            'return'
        );
        if ($annotType->isMixed() && isset($type)) {
            return $type;
        } else {
            return $annotType;
        }
    }

    /**
     * Parses the docblock of the method to get return class type
     *
     * @param ReflectionMethod $method
     * @return string|null
     */
    public function getReturnClass(ReflectionMethod $method)
    {
        return $this->getClassType($this->getReturnType($method));
    }

    protected function getClassType(VarType $type)
    {
        if ($type->isObjectType()) {
            return $type->getType();
        }
    }

    protected function parseAnnotationType($docBlock, ReflectionClass $declaringClass, $annotationName)
    {
        if (!preg_match('/@'.$annotationName.'\s+(\S+)/', $docBlock, $matches)) {
            return VarType::mixed();
        }
        return $this->parseType($matches[1], $declaringClass);
    }

    /**
     * Parses var type
     *
     * @param string $value
     * @param ReflectionClass $declaringClass
     * @return VarType
     */
    protected function parseType($value, ReflectionClass $declaringClass)
    {
        if (empty($value)) {
            throw new InvalidArgumentException("type cannot be empty");
        } elseif (!is_string($value)) {
            throw new InvalidArgumentException("type should be string, got " . VarType::describe($value));
        }

        if (strpos($value, '|') !== false) {
            $types = [];
            foreach (explode('|', $value) as $oneType) {
                $types[] = $this->parseType($oneType, $declaringClass);
            }
            return VarType::multipleType($types);
        } elseif ($value === 'array') {
            return VarType::arrayType(VarType::mixed());
        } elseif (preg_match('/array<(.*)>/', $value, $arrayTypes)
                  || preg_match('/(.*)\[\]$/', $value, $arrayTypes)) {
            return VarType::arrayType($this->parseType($arrayTypes[1], $declaringClass));
        } elseif (in_array($value, ['self', 'static'])) {
            return VarType::objectType($declaringClass->getName());
        } else {
            $isFqcn = false;
            if ($value[0] === '\\') {
                $isFqcn = true;
                $type = substr($value, 1);
            } else {
                $type = $value;
            }
            if (!preg_match(VarType::CLASS_NAME_REGEX, $type)) {
                throw new InvalidArgumentException("Invalid type declaration '{$type}'");
            }
            if (VarType::isPrimitiveType($type)) {
                return VarType::primitiveType($type);
            } else {
                if (!$isFqcn) {
                    $file = $declaringClass->getFileName();
                    if ($file === false) {
                        throw new InvalidArgumentException("Cannot resolve class name from " . $declaringClass->getName());
                    }
                    $file = new ReflectionFile($file);
                    $type = $file->resolveClassName($type, $declaringClass->getNamespaceName());
                }
                if (!(class_exists($type) || interface_exists($type))) {
                    throw new ClassNotFoundException("Class '{$type}' does not exist");
                }
                return VarType::objectType($type);
            }
        }
    }

    protected function getMethodDocComment(ReflectionMethod $method)
    {
        $doc = $method->getDocComment();
        $name = $method->getName();
        if (stripos($doc, '@inheritdoc') !== false) {
            $class = $method->getDeclaringClass();
            if (false !== ($parent = $class->getParentClass())) {
                if ($parent->hasMethod($name)) {
                    return $this->getMethodDocComment($parent->getMethod($name));
                }
            }
            foreach ($class->getInterfaces() as $interface) {
                if ($interface->hasMethod($name)) {
                    return $interface->getMethod($name)->getDocComment();
                }
            }
        } else {
            return $doc;
        }
    }
}
