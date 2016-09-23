<?php
namespace kuiper\reflection;

use InvalidArgumentException;
use UnexpectedValueException;
use ReflectionType;

final class VarType
{
    const CLASS_NAME_REGEX = '/^\\\\?([a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*\\\\)*[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*$/';

    private static $PRIMITIVE_TYPES = [
        'boolean',
        'integer',
        'double',
        'string',
        'mixed',
        'null',
        'callable',
        'resource',
        'void' => 'null',
        'int' => 'integer',
        'class' => 'string',
        'const' => 'string',
        'object' => 'mixed',
        'float' => 'double',
        'bool' => 'boolean',
        'false' => 'boolean',
        'true' => 'boolean'
    ];

    /**
     * @var bool
     */
    private $isPrimitive;

    /**
     * @var bool
     */
    private $isArray;

    /**
     * @var string|VarType|array<VarType>
     */
    private $type;

    /**
     * @var mixed
     */
    private $value;

    private function __construct($type, $isPrimitive = true, $isArray = false)
    {
        $this->value = $type;
        $this->type = $type;
        if ($isPrimitive && !in_array($type, self::$PRIMITIVE_TYPES)) {
            if (!isset(self::$PRIMITIVE_TYPES[$type])) {
                throw new InvalidArgumentException("unknown primitive type {$type}");
            }
            $this->type = self::$PRIMITIVE_TYPES[$type];
        }
        if (is_array($this->type)) {
            foreach ($this->type as $subType) {
                if (!($subType instanceof VarType)) {
                    throw new InvalidArgumentException(sprintf(
                        "multiple type required parameter be instanceof %s, got %s",
                        __CLASS__,
                        self::describe($subType)
                    ));
                }
            }
        }
        $this->isPrimitive = $isPrimitive;
        if ($isArray && !($type instanceof VarType)) {
            throw new InvalidArgumentException(sprintf(
                "array type required parameter be instanceof %s, got %s",
                __CLASS__,
                self::describe($type)
            ));
        }
        $this->isArray = $isArray;
    }

    public function isObjectType()
    {
        return !$this->isPrimitive() && is_string($this->type);
    }

    /**
     * @return boolean return true if type is array
     */
    public function isArray()
    {
        return $this->isArray;
    }

    /**
     * @return boolean return true if type is primitive
     */
    public function isPrimitive()
    {
        return $this->isPrimitive;
    }

    /**
     * @return boolean return true if type are multiple types
     */
    public function isMulitiple()
    {
        return is_array($this->type);
    }

    /**
     * @return boolean return true if type is mixed
     */
    public function isMixed()
    {
        return $this->isPrimitive() && $this->type === 'mixed';
    }

    /**
     * @return boolean return true if type is mixed or type is mixed array
     */
    public function isUnknown()
    {
        return $this->isMixed() || ($this->isArray() && $this->type->isMixed());
    }

    public function getType()
    {
        return $this->type;
    }

    public function getDeclaringType()
    {
        return $this->value;
    }

    /**
     * checks whether the value is valid
     *
     * @param mixed $value 
     * @return boolean 
     */
    public function validate($value)
    {
        if ($this->isPrimitive()) {
            switch ($this->type) {
                case 'boolean':
                    return in_array($value, ['true', 'false', true, false, '1', '0', 1, 0], true);
                case 'integer':
                    return is_numeric($value) && $value == (int) $value;
                case 'double':
                    return is_numeric($value);
                case 'string':
                    return is_string($value);
                case 'null':
                    return $value === null;
                case 'callable':
                    return is_callable($value);
                case 'resource':
                    return is_resource($value);
                case 'mixed':
                    return true;
                default:
                    throw new UnexpectedValueException("primitive type '{$this->type}' is invalid");
            }
        } elseif ($this->isArray()) {
            if (!is_array($value)) {
                return false;
            }
            foreach ($value as $item) {
                if (!$this->type->validate($item)) {
                    return false;
                }
            }
            return true;
        } elseif ($this->isMulitiple()) {
            foreach ($this->type as $subType) {
                if ($subType->validate($value)) {
                    return true;
                }
            }
            return false;
        } elseif ($this->isObjectType()) {
            return $value instanceof $this->type;
        } else {
            throw new UnexpectedValueException("unexpect type '{$this->type}'");
        }
    }

    /**
     * @param mixed $value
     * @return mixed
     */
    public function sanitize($value)
    {
        if ($this->isPrimitive()) {
            switch ($this->type) {
                case 'boolean':
                    return in_array($value, [true, 'true', 1, '1'], true);
                case 'integer':
                    return intval($value);
                case 'double':
                    return floatval($value);
                case 'string':
                    return (string) $value;
                case 'null':
                    return null;
                case 'callable':
                case 'resource':
                case 'mixed':
                    return $value;
                default:
                    throw new UnexpectedValueException("primitive type '{$this->type}' is invalid");
            }
        } elseif ($this->isArray()) {
            $value = (array) $value;
            $result = [];
            foreach ($value as $key => $item) {
                $result[$key] = $this->type->sanitize($item);
            }
            return $result;
        } else {
            return $value;
        }
    }

    public function __toString()
    {
        if ($this->isPrimitive()) {
            return $this->value;
        } elseif ($this->isArray()) {
            return sprintf('array<%s>', $this->type);
        } elseif (is_array($this->type)) {
            return implode('|', $this->type);
        } elseif (is_string($this->type)) {
            return $this->type;
        } else {
            return 'unknown type';
        }
    }

    /**
     * creates a mixed type
     * 
     * @return VarType mixed type
     */
    public static function mixed()
    {
        return new self('mixed');
    }

    /**
     * creates an integer type
     * 
     * @return VarType integer type
     */
    public static function integer()
    {
        return new self('integer');
    }

    /**
     * creates a boolean type
     * 
     * @return VarType boolean type
     */
    public static function boolean()
    {
        return new self('boolean');
    }

    /**
     * creates a double type
     * 
     * @return VarType double type
     */
    public static function double()
    {
        return new self('double');
    }

    /**
     * creates a string type
     * 
     * @return VarType string type
     */
    public static function string()
    {
        return new self('string');
    }

    /**
     * creates a null type
     * 
     * @return VarType null 
     */
    public static function null()
    {
        return new self('null');
    }

    /**
     * creates a primitive type
     * 
     * @param string $value
     * @return VarType primitive type
     */
    public static function primitiveType($value)
    {
        if (!self::isPrimitiveType($value)) {
            throw new InvalidArgumentException(sprintf(
                "'%s' is not a primitive type, all primitive types are: %s",
                $value,
                json_encode(array_unique(array_values(self::$PRIMITIVE_TYPES)))
            ));
        }
        return new self($value);
    }

    /**
     * creates an array type
     * 
     * @param VarType $elemType
     * @return VarType array type
     */
    public static function arrayType(VarType $elemType)
    {
        return new self($elemType, $isPrimitive = false, $isArray = true);
    }

    /**
     * creates an object type
     * 
     * @param string $className
     * @return VarType object type
     */
    public static function objectType($className)
    {
        return new self($className, $isPrimitive = false);
    }

    /**
     * creates a mixed type with multiple choices
     *
     * @param array $types
     * @return VarType
     */
    public static function multipleType(array $types)
    {
        return new self($types, $isPrimitive = false);
    }

    /**
     * creates type from ReflectionType
     *
     * @param ReflectionType $type
     * @return VarType
     */
    public static function fromReflectionType(ReflectionType $type)
    {
        $typeValue = (string) $type;
        if ($type->isBuiltin()) {
            if ($typeValue === 'array') {
                return VarType::arrayType(VarType::mixed());
            } else {
                return VarType::primitiveType($typeValue);
            }
        } elseif (class_exists($typeValue) || interface_exists($typeValue)) {
            return VarType::objectType($typeValue);
        } else {
            return VarType::mixed();
        }
    }

    /**
     * checks whether the given type is a primitive type
     *
     * @param string $value
     * @return boolean
     */
    public static function isPrimitiveType($value)
    {
        return in_array($value, self::$PRIMITIVE_TYPES)
            || isset(self::$PRIMITIVE_TYPES[$value]);
    }

    /**
     * Describes type of value
     *
     * @param mixed $value
     * @return string
     */
    public static function describe($value)
    {
        $type = gettype($value);
        if ($type === "object") {
            return get_class($value);
        } elseif (in_array($type, ["array", "resource", "unknown type"])) {
            return $type;
        } else {
            return json_encode($value);
        }
    }

    /**
     * creates type from string
     * 
     * @param string $type
     * @return VarType
     */
    public static function parse($type)
    {
        if (empty($type)) {
            throw new InvalidArgumentException("type cannot be empty");
        }
        if (!is_string($type)) {
            throw new InvalidArgumentException("type should be string, got " . self::describe($type));
        }
        if (strpos($type, '|') !== false) {
            $types = [];
            foreach (explode('|', $type) as $oneType) {
                $types[] = self::parse($oneType);
            }
            return self::multipleType($types);
        } elseif ($type === 'array') {
            return self::arrayType(self::mixed());
        } elseif (preg_match('/array<(.*)>/', $type, $arrayTypes)
                  || preg_match('/(.*)\[\]$/', $type, $arrayTypes)) {
            return self::arrayType(self::parse($arrayTypes[1]));
        } else {
            if (!preg_match(self::CLASS_NAME_REGEX, $type)) {
                throw new InvalidArgumentException("Invalid type declaration '{$type}'");
            }
            if (VarType::isPrimitiveType($type)) {
                return self::primitiveType($type);
            } else {
                return self::objectType($type);
            }
        }
    }
}
