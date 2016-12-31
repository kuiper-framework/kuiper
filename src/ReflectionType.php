<?php

namespace kuiper\reflection;

use InvalidArgumentException;
use LogicException;
use ReflectionType as Php7ReflectionType;

/**
 * 目前只实现解析不能包含括号的简单类型.
 */
final class ReflectionType implements ReflectionTypeInterface
{
    const CLASS_NAME_REGEX = '/^\\\\?([a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*\\\\)*[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*$/';

    private static $BUILTIN_TYPES = [
        'bool',
        'int',
        'double',
        'string',
        'mixed',
        'null',
        'object',
        'callable',
        'resource',
        'void' => 'null',
        'integer' => 'int',
        'object' => 'mixed',
        'float' => 'double',
        'boolean' => 'bool',
        'false' => 'bool',
        'true' => 'bool',
    ];

    /**
     * @var string|null
     */
    private $builtinType;

    /**
     * @var string|null
     */
    private $className;

    /**
     * @var ReflectionTypeInterface|null
     */
    private $arrayValueType;

    /**
     * @var ReflectionTypeInterface[]|null
     */
    private $compoundTypes;

    private static $TYPES;

    private function __construct($builtinType, $className = null, ReflectionType $arrayValueType = null, array $compoundTypes = null)
    {
        if (isset($builtinType)) {
            if (!in_array($builtinType, self::$BUILTIN_TYPES)) {
                throw new InvalidArgumentException("unknown built type {$builtinType}");
            }
            $this->builtinType = $builtinType;
        } elseif (isset($className)) {
            $this->className = $className;
        } elseif (isset($arrayValueType)) {
            $this->arrayValueType = $arrayValueType;
        } elseif (isset($compoundTypes)) {
            $this->compoundTypes = $compoundTypes;
        }
    }

    /**
     * Describes type of value.
     *
     * @param mixed $value
     *
     * @return string
     */
    public static function describe($value)
    {
        $type = gettype($value);
        if ($type === 'object') {
            return get_class($value);
        } elseif (in_array($type, ['array', 'resource', 'unknown type'])) {
            return $type;
        } else {
            return json_encode($value);
        }
    }

    /**
     * {@inheritdoc}
     */
    public static function parse($type)
    {
        if (empty($type)) {
            throw new InvalidArgumentException('type cannot be empty');
        }
        if (!is_string($type)) {
            throw new InvalidArgumentException('type should be string, got '.self::describe($type));
        }
        if (strpos($type, '|') !== false) {
            $types = [];
            foreach (explode('|', $type) as $oneType) {
                $types[] = self::parse($oneType);
            }

            return self::compoundType($types);
        } elseif ($type === 'array') {
            return self::arrayType(self::mixed());
        } elseif (preg_match('/array<(.*)>/', $type, $arrayTypes)
                  || preg_match('/(.*)\[\]$/', $type, $arrayTypes)) {
            return self::arrayType(self::parse($arrayTypes[1]));
        } else {
            if (!preg_match(self::CLASS_NAME_REGEX, $type)) {
                throw new InvalidArgumentException("Invalid type declaration '{$type}'");
            }
            if (self::isBuiltinType($type)) {
                return self::builtinType($type);
            } else {
                return self::objectType($type);
            }
        }
    }

    /**
     * creates type from ReflectionType.
     *
     * @param ReflectionType $type
     *
     * @return static
     */
    public static function fromReflectionType(Php7ReflectionType $type)
    {
        $typeValue = (string) $type;
        if ($type->isBuiltin()) {
            if ($typeValue === 'array') {
                return self::arrayType(self::mixed());
            } else {
                return self::builtinType($typeValue);
            }
        } elseif (class_exists($typeValue) || interface_exists($typeValue)) {
            return self::objectType($typeValue);
        } else {
            return self::mixed();
        }
    }

    /**
     * creates a mixed type.
     *
     * @return static mixed type
     */
    public static function mixed()
    {
        return self::builtinType('mixed');
    }

    /**
     * creates an integer type.
     *
     * @return static integer type
     */
    public static function integer()
    {
        return self::builtinType('int');
    }

    /**
     * creates a boolean type.
     *
     * @return static boolean type
     */
    public static function boolean()
    {
        return self::builtinType('bool');
    }

    /**
     * creates a double type.
     *
     * @return static double type
     */
    public static function double()
    {
        return self::builtinType('double');
    }

    /**
     * creates a string type.
     *
     * @return static string type
     */
    public static function string()
    {
        return self::builtinType('string');
    }

    /**
     * creates a null type.
     *
     * @return static null
     */
    public static function null()
    {
        return self::builtinType('null');
    }

    /**
     * creates a builtin type.
     *
     * @param string $type
     *
     * @return static
     */
    private static function builtinType($type)
    {
        if (in_array($type, self::$BUILTIN_TYPES)) {
            // pass
        } elseif (isset(self::$BUILTIN_TYPES[$type])) {
            $type = self::$BUILTIN_TYPES[$type];
        }

        if (isset(self::$TYPES[$type])) {
            return self::$TYPES[$type];
        }

        if (!self::isBuiltinType($type)) {
            throw new InvalidArgumentException(sprintf(
                "'%s' is not a builtin type, all builtin types are: %s",
                $type,
                json_encode(array_unique(array_values(self::$BUILTIN_TYPES)))
            ));
        }

        return self::$TYPES[$type] = new self($type);
    }

    /**
     * creates an array type.
     *
     * @param ReflectionTypeInterface $arrayValueType
     *
     * @return static
     */
    public static function arrayType(ReflectionTypeInterface $arrayValueType)
    {
        return new self(null, $className = null, $arrayValueType);
    }

    /**
     * creates an object type.
     *
     * @param string $className
     *
     * @return static
     */
    public static function objectType($className)
    {
        return new self(null, $className);
    }

    /**
     * creates a mixed type with multiple choices.
     *
     * @param array $types
     *
     * @return static
     */
    public static function compoundType(array $types)
    {
        return new self(null, $className = null, $arrayType = null, $types);
    }

    /**
     * checks whether the given type is a builtin type.
     *
     * @param string $value
     *
     * @return bool
     */
    private static function isBuiltinType($value)
    {
        return in_array($value, self::$BUILTIN_TYPES)
            || isset(self::$BUILTIN_TYPES[$value]);
    }

    /**
     * {@inheritdoc}
     */
    public function isArray()
    {
        return isset($this->arrayValueType);
    }

    /**
     * {@inheritdoc}
     */
    public function getArrayValueType()
    {
        return $this->arrayValueType;
    }

    /**
     * {@inheritdoc}
     */
    public function isCompound()
    {
        return isset($this->compoundTypes);
    }

    /**
     * {@inheritdoc}
     */
    public function getCompoundTypes()
    {
        return $this->compoundTypes;
    }

    /**
     * {@inheritdoc}
     */
    public function isNullable()
    {
        if ($this->isNull()) {
            return true;
        }
        if (isset($this->compoundTypes)) {
            foreach ($this->compoundTypes as $type) {
                if ($type->isNull()) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function isObject()
    {
        return $this->builtinType === 'object' || isset($this->className);
    }

    /**
     * {@inheritdoc}
     */
    public function getClassName()
    {
        return $this->className;
    }

    /**
     * {@inheritdoc}
     */
    public function isBuiltin()
    {
        return isset($this->builtinType);
    }

    /**
     * {@inheritdoc}
     */
    public function getBuiltinType()
    {
        return $this->builtinType;
    }

    /**
     * {@inheritdoc}
     */
    public function isMixed()
    {
        return $this->builtinType === 'mixed';
    }

    /**
     * {@inheritdoc}
     */
    public function isNull()
    {
        return $this->builtinType === 'null';
    }

    /**
     * {@inheritdoc}
     */
    public function __toString()
    {
        if ($this->isBuiltin()) {
            return $this->getBuiltinType();
        } elseif ($this->isArray()) {
            if ($this->arrayValueType->isMixed()) {
                return 'array';
            }

            return sprintf('%s[]', $this->arrayValueType);
        } elseif ($this->isCompound()) {
            return implode('|', $this->compoundTypes);
        } elseif ($this->hasClassName()) {
            return $this->className;
        } else {
            return 'mixed';
        }
    }

    private function hasClassName()
    {
        return isset($this->className);
    }

    /**
     * {@inheritdoc}
     */
    public function validate($value)
    {
        if ($this->isBuiltin()) {
            switch ($this->builtinType) {
                case 'bool':
                    return in_array($value, ['true', 'false', true, false, '1', '0', 1, 0], true);
                case 'int':
                    return is_numeric($value) && $value == (int) $value;
                case 'double':
                    return is_numeric($value);
                case 'string':
                    return is_scalar($value);
                case 'null':
                    return $value === null;
                case 'callable':
                    return is_callable($value);
                case 'resource':
                    return is_resource($value);
                case 'mixed':
                    return true;
                default:
                    throw new LogicException("builtin type '{$this->builtinType}' is invalid");
            }
        } elseif ($this->isArray()) {
            if (!is_array($value)) {
                return false;
            }
            foreach ($value as $item) {
                if (!$this->arrayValueType->validate($item)) {
                    return false;
                }
            }

            return true;
        } elseif ($this->isCompound()) {
            foreach ($this->compoundTypes as $subType) {
                if ($subType->validate($value)) {
                    return true;
                }
            }

            return false;
        } elseif ($this->hasClassName()) {
            return $value instanceof $this->className;
        } else {
            throw new LogicException("unexpect type '{$this}'");
        }
    }

    /**
     * {@inheritdoc}
     */
    public function sanitize($value)
    {
        if ($this->isBuiltin()) {
            switch ($this->builtinType) {
                case 'bool':
                    return in_array($value, [true, 'true', 1, '1'], true);
                case 'int':
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
                    throw new LogicException("builtin type '{$this->builtinType}' is invalid");
            }
        } elseif ($this->isArray()) {
            $value = (array) $value;
            $result = [];
            foreach ($value as $key => $item) {
                $result[$key] = $this->arrayValueType->sanitize($item);
            }

            return $result;
        } else {
            return $value;
        }
    }
}
