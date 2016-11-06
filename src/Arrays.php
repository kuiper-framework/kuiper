<?php
namespace kuiper\helper;

use InvalidArgumentException;
use ArrayAccess;
use ReflectionClass;

class Arrays
{
    /**
     * Gets object field value by getter method
     */
    const GETTER = 'getter';

    /**
     * Gets object field value by property
     */
    const OBJ = 'obj';
    
    /**
     * Gets value from array by key
     *
     * @param ArrayAccess $array
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public static function fetch($array, $key, $default = null)
    {
        return isset($array[$key]) ? $array[$key] : $default;
    }

    /**
     * Collects value from array
     *
     * @param array|\Iterator $array
     * @param string $name
     * @param string $type
     * @return array
     */
    public static function pull($arr, $name, $type = null)
    {
        $ret = [];
        if ($type == self::GETTER) {
            $method = 'get' . $name;
            foreach ($arr as $elem) {
                $ret[] = $elem->$method();
            }
        } elseif ($type == self::OBJ) {
            foreach ($arr as $elem) {
                $ret[] = $elem->$name;
            }
        } else {
            foreach ($arr as $elem) {
                $ret[] = $elem[$name];
            }
        }
        return $ret;
    }

    /**
     * Creates associated array
     *
     * @param array|\Iterator $array
     * @param string $name
     * @param string $type
     * @return array
     */
    public static function assoc($arr, $name, $type = null)
    {
        $ret = [];
        if (empty($arr)) {
            return $ret;
        }
        if ($type == self::GETTER) {
            $method = 'get' . $name;
            foreach ($arr as $elem) {
                $ret[$elem->$method()] = $elem;
            }
        } elseif ($type == self::OBJ) {
            foreach ($arr as $elem) {
                $ret[$elem->$name] = $elem;
            }
        } else {
            foreach ($arr as $elem) {
                $ret[$elem[$name]] = $elem;
            }
        }
        return $ret;
    }

    /**
     * Excludes key in given keys
     *
     * @param array $array
     * @param array $excludedKeys
     * @return array
     */
    public static function exclude(array $arr, array $excludedKeys)
    {
        return array_diff_key($arr, array_flip($excludedKeys));
    }

    /**
     * Create array with given keys
     *
     * @param array $array
     * @param array $includedKeys
     * @return array
     */
    public static function select($arr, array $includedKeys)
    {
        $ret = [];
        if ($arr instanceof ArrayAccess) {
            foreach ($includedKeys as $key) {
                if ($arr->offsetExists($key)) {
                    $ret[$key] = $arr[$key];
                }
            }
        } else {
            foreach ($includedKeys as $key) {
                if (array_key_exists($key, $arr)) {
                    $ret[$key] = $arr[$key];
                }
            }
        }
        return $ret;
    }

    /**
     * Filter null value
     *
     * @param array $arr
     * @return array
     */
    public static function filter(array $arr)
    {
        return array_filter($arr, function ($elem) {
            return isset($elem);
        });
    }

    /**
     * @param object $bean
     * @param array|\Iterator $attrs
     * @param bool $onlyPublic
     */
    public static function assign($bean, $attrs, $onlyPublic = true)
    {
        if ($bean === null || !is_object($bean)) {
            throw new InvalidArgumentException("Parameter 'bean' need be an object, got " . gettype($bean));
        }
        if ($bean instanceof ArrayAccess) {
            foreach ($attrs as $name => $val) {
                $bean[$name] = $val;
            }
        } else {
            $properties = get_object_vars($bean);
            foreach ($attrs as $name => $val) {
                if (array_key_exists($name, $properties)) {
                    $bean->{$name} = $val;
                } elseif (method_exists($bean, $method = 'set' . Text::camelize($name))) {
                    $bean->$method($val);
                } elseif (!$onlyPublic) {
                    $failed[$name] = $val;
                }
            }
            if (!$onlyPublic && !empty($failed)) {
                $class = new ReflectionClass($bean);
                foreach ($failed as $name => $val) {
                    if (!$class->hasProperty($name)) {
                        continue;
                    }
                    $property = $class->getProperty($name);
                    if ($property) {
                        $property->setAccessible(true);
                        $property->setValue($bean, $val);
                    }
                }
            }
        }
        return $bean;
    }

    /**
     * @param object|array $bean
     * @param bool $includeGetters
     * @return array
     */
    public static function toArray($bean, $includeGetters = true, $uncamelizeKey = false)
    {
        if ($bean === null || !is_object($bean)) {
            throw new InvalidArgumentException("Parameter 'bean' need be an object, got " . gettype($bean));
        }
        $properties = get_object_vars($bean);
        if ($includeGetters) {
            $class = new ReflectionClass($bean);
            foreach ($class->getMethods() as $method) {
                if ($method->isStatic() || !$method->isPublic()) {
                    continue;
                }
                if (preg_match('/^(get|is)(.+)/', $method->getName(), $matches)
                    && $method->getNumberOfParameters() === 0) {
                    $key = lcfirst(preg_replace('/^get/', '', $matches[0]));
                    $properties[$key] = $method->invoke($bean);
                }
            }
        }
        if ($uncamelizeKey) {
            $values = [];
            foreach ($properties as $key => $val) {
                $values[Text::uncamelize($key)] = $val;
            }
            return $values;
        } else {
            return $properties;
        }
    }

    /**
     * create sorter
     *
     * @param string $name
     * @param callable $func
     * @param string $type
     * @return callable
     */
    public static function sorter($name, $func = null, $type = null)
    {
        if (!isset($func)) {
            $func = function ($a, $b) {
                if ($a == $b) {
                    return 0;
                }
                return $a < $b ? -1 : 1;
            };
        }
        
        if ($type == self::GETTER) {
            $method = 'get' . $name;
            return function ($a, $b) use ($method, $func) {
                return call_user_func($func, $a->$method(), $b->$method());
            };
        } elseif ($type == self::OBJ) {
            return function ($a, $b) use ($name, $func) {
                return call_user_func($func, $a->$name, $b->$name);
            };
        } else {
            return function ($a, $b) use ($name, $func) {
                return call_user_func($func, $a[$name], $b[$name]);
            };
        }
    }

    /**
     * Applies the callback to the keys of given array
     *
     * @param array $arr
     * @param callable $callback
     */
    public static function mapKeys(array $arr, callable $callback)
    {
        $result = [];
        array_walk($arr, function(&$value, $key) use (&$result, $callback) {
            $result[$callback($key)] = $value;
        });
        return $result;
    }

    public static function uncamelizeKeys(array $arr)
    {
        return self::mapKeys($arr, [Text::class, 'uncamelize']);
    }
}
