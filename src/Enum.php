<?php
namespace kuiper\helper;

use InvalidArgumentException;
use ReflectionClass;

/**
 * enum class
 */
abstract class Enum implements \JsonSerializable
{
    /**
     * key = className
     * value = array which key is enum value
     */
    private static $values = array();

    /**
     * key = className
     * value = array which key is enum name
     */
    private static $names = array();

    /**
     * properties for enum instances
     */
    protected static $PROPERTIES = [];

    /**
     * @var string name of enum
     */
    protected $name;

    /**
     * @var mixed value of enum
     */
    protected $value;

    /**
     * Constructor.
     */
    protected function __construct($name, $value)
    {
        $this->name = $name;
        $this->value = $value;
    }

    /**
     * Gets name of enum instance
     *
     * @return string
     */
    public function name()
    {
        return $this->name;
    }

    /**
     * Gets value of enum instance
     *
     * @return mixed
     */
    public function value()
    {
        return $this->value;
    }

    /**
     * default to string method
     *
     * @return string name of enum
     */
    public function __toString()
    {
        return $this->name;
    }

    /**
     * Gets properties
     *
     * @return mixed
     */
    public function __get($name)
    {
        if (isset(static::$PROPERTIES[$name][$this->value])) {
            return static::$PROPERTIES[$name][$this->value];
        } elseif (property_exists($this, $name)) {
            return $this->$name;
        }
    }

    public function __isset($name)
    {
        return isset(static::$PROPERTIES[$name][$this->value])
            || isset($this->$name);
    }
    
    /**
     * Gets all enum values
     *
     * @return array
     */
    public static function values()
    {
        return array_keys(static::getValues());
    }

    /**
     * Gets all enum names
     *
     * @return array
     */
    public static function names()
    {
        return array_keys(static::getNames());
    }

    /**
     * Gets all enum oridinals
     *
     * @return array
     */
    public static function ordinals()
    {
        if (empty(static::$PROPERTIES['ordinal'])) {
            throw new \RuntimeException("property 'ordinal' is not defined, please set value for " . get_called_class() . '::$PROPERTIES["ordinal"]');
        }
        return array_values(static::$PROPERTIES['ordinal']);
    }
    
    /**
     * Gets all enums
     *
     * @return Enum[]
     */
    public static function instances()
    {
        return array_values(self::getValues());
    }
    
    /**
     * Checks whether the enum value exists
     * @return boolean
     */
    public static function hasValue($value)
    {
        return array_key_exists($value, static::getValues());
    }

    /**
     * Gets the name for the enum value
     * @return string
     */
    public static function nameOf($value)
    {
        $values = static::getValues();
        return isset($values[$value]) ? $values[$value]->name() : null;
    }

    /**
     * Checks whether the name of enum value exists
     * @return boolean
     */
    public static function hasName($name)
    {
        return array_key_exists($name, static::getNames());
    }

    /**
     * Gets the enum value for the name
     *
     * @return mixed value of
     */
    public static function valueOf($name)
    {
        $names = static::getNames();
        return isset($names[$name]) ? $names[$name] : null;
    }

    /**
     * Gets the enum instance for the name
     *
     * @param string $name
     * @param object $default 
     *  
     * @return Enum
     */
    public static function fromName($name, $default = null)
    {
        $names = static::getNames();
        if (array_key_exists($name, $names)) {
            return self::fromValue($names[$name]);
        }
        if ($default === null) {
            throw new InvalidArgumentException("No enum constant '$name' in class " . get_called_class());
        } 
        return $default;
    }
    
    /**
     * Gets the enum instance for the value
     *
     * @param mixed $value
     * @param object $default
     *
     * @return Enum
     */
    public static function fromValue($value, $default = null)
    {
        $values = static::getValues();
        if (array_key_exists($value, $values)) {
            return $values[$value];
        }
        if ($default === null) {
            throw new InvalidArgumentException("No enum constant value '$value' class " . get_called_class());
        } 
        return $default;
    }

    /**
     * Gets the enum instance match properties ordinal
     *
     * @param int $ordinal
     * @param object $default
     *
     * @return Enum
     */
    public static function fromOrdinal($ordinal, $default = null)
    {
        if (empty(static::$PROPERTIES['ordinal'])) {
            throw new \RuntimeException("property 'ordinal' is not defined, please set value for " . get_called_class() . '::$PROPERTIES["ordinal"]');
        }
        $value = array_search($ordinal, static::$PROPERTIES['ordinal']);
        if ($value !== false) {
            return self::fromValue($value);
        } else {
            if ($default === null) {
                throw new InvalidArgumentException("No enum oridinal for '$value' class " . get_called_class());
            }
            return $default;
        }
    }
    
    /**
     * Returns a value when called statically like so: MyEnum::SOME_VALUE() given SOME_VALUE is a class constant
     *
     * @param string $name
     * @param array  $arguments
     *
     * @return static
     * @throws InvalidArgumentException
     */
    public static function __callStatic($name, $arguments)
    {
        return static::fromName($name);
    }

    public function jsonSerialize()
    {
        return $this->name;
    }

    protected static function getValues()
    {
        $class = get_called_class();
        if (!array_key_exists($class, self::$values)) {
            $reflect = new ReflectionClass($class);
            $constants = $reflect->getConstants();
            self::$names[$class] = $constants;
            $flip = [];
            foreach ($constants as $name => $val) {
                $flip[$val] = new $class($name, $val);
            }
            // array_flip cannot use here, if value is true or false, the following error will occur:
            // array_flip(): Can only flip STRING and INTEGER values! on line 1
            self::$values[$class] = $flip;
        }
        return self::$values[$class];
    }

    protected static function getNames()
    {
        $class = get_called_class();
        if (!isset(self::$names[$class])) {
            self::getValues($class);
        }
        return self::$names[$class];
    }
}
