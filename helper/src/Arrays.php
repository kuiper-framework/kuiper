<?php

declare(strict_types=1);

namespace kuiper\helper;

class Arrays
{
    /**
     * Collects value from array.
     *
     * @param array|\Iterator $arr
     * @param string          $name
     */
    public static function pull($arr, $name): array
    {
        $ret = [];
        foreach ($arr as $elem) {
            if (is_object($elem)) {
                $method = 'get'.$name;
                $ret[] = $elem->$method();
            } else {
                $ret[] = $elem[$name] ?? null;
            }
        }

        return $ret;
    }

    public static function pullField($arr, $name): array
    {
        $ret = [];
        foreach ($arr as $elem) {
            $ret[] = $elem->$name;
        }

        return $ret;
    }

    /**
     * Creates associated array.
     *
     * @param array|\Iterator $arr
     * @param string|callable $name
     */
    public static function assoc($arr, $name): array
    {
        $ret = [];

        foreach ($arr as $elem) {
            if (is_callable($name)) {
                $ret[$name($elem)] = $elem;
            } elseif (is_object($elem)) {
                $method = 'get'.$name;
                $ret[$elem->$method()] = $elem;
            } else {
                $ret[$elem[$name] ?? null] = $elem;
            }
        }

        return $ret;
    }

    public static function assocByField($arr, $name): array
    {
        return self::assoc($arr, static function ($item) use ($name) {
            return $item->$name;
        });
    }

    public static function toMap($arr, $name): array
    {
        return self::assoc($arr, $name);
    }

    public static function groupBy($arr, $groupBy): array
    {
        $ret = [];
        foreach ($arr as $elem) {
            if (is_callable($groupBy)) {
                $key = $groupBy($elem);
            } elseif (is_object($elem)) {
                $method = 'get'.$groupBy;
                $key = $elem->$method();
            } else {
                $key = $elem[$groupBy] ?? null;
            }
            if (is_null($key) || is_scalar($key)) {
                $ret[$key][] = $elem;
            } else {
                throw new \InvalidArgumentException("Cannot group by key '$groupBy', support only scalar type, got ".(is_object($key) ? get_class($key) : gettype($key)));
            }
        }

        return $ret;
    }

    /**
     * Excludes key in given keys.
     */
    public static function exclude(array $arr, array $excludedKeys): array
    {
        return array_diff_key($arr, array_flip($excludedKeys));
    }

    /**
     * Changes key name.
     */
    public static function rename(array $arr, array $columnMap): array
    {
        $ret = $arr;
        foreach ($columnMap as $key => $newKey) {
            if (array_key_exists($key, $arr)) {
                unset($ret[$key]);
                $ret[$newKey] = $arr[$key];
            }
        }

        return $ret;
    }

    public static function flatten(array $arr, int $depth = 1, bool $keepKeys = false): array
    {
        if (empty($arr) || $depth < 1) {
            return $arr;
        }
        if (1 === $depth) {
            foreach ($arr as $item) {
                if (!is_array($item)) {
                    throw new \InvalidArgumentException('element type is not array');
                }
            }

            return $keepKeys
                ? array_merge(...array_values($arr))
                : array_merge(...array_map('array_values', array_values($arr)));
        }

        $items = [];
        foreach ($arr as $item) {
            $items[] = self::flatten($item, $depth - 1, $keepKeys);
        }

        return array_merge(...$items);
    }

    /**
     * Create array with given keys.
     *
     * @param array|object $arr
     */
    public static function select($arr, array $includedKeys): array
    {
        $ret = [];
        if (is_object($arr)) {
            foreach ($includedKeys as $name) {
                $method = 'get'.$name;
                if (method_exists($arr, $method)) {
                    $ret[$name] = $arr->$method();
                }
            }
        } elseif ($arr instanceof \ArrayAccess) {
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

    public static function selectField($arr, array $includedKeys): array
    {
        $ret = [];
        foreach ($includedKeys as $name) {
            if (isset($arr->$name)) {
                $ret[$name] = $arr->$name;
            }
        }

        return $ret;
    }

    /**
     * Filter null value.
     */
    public static function filter(array $arr): array
    {
        return array_filter($arr, static function ($elem) {
            return isset($elem);
        });
    }

    /**
     * @param object          $bean
     * @param array|\Iterator $attributes
     * @param bool            $onlyPublic
     *
     * @return object
     */
    public static function assign($bean, $attributes, $onlyPublic = true)
    {
        if (null === $bean || !is_object($bean)) {
            throw new \InvalidArgumentException("Parameter 'bean' need be an object, got ".gettype($bean));
        }
        if ($bean instanceof \ArrayAccess) {
            foreach ($attributes as $name => $val) {
                $bean[$name] = $val;
            }
        } else {
            $properties = get_object_vars($bean);
            foreach ($attributes as $name => $val) {
                if (array_key_exists($name, $properties)) {
                    $bean->{$name} = $val;
                } elseif (method_exists($bean, $method = 'set'.Text::camelCase($name))) {
                    $bean->$method($val);
                } elseif (!$onlyPublic) {
                    $failed[$name] = $val;
                }
            }
            if (!$onlyPublic && !empty($failed)) {
                try {
                    $class = new \ReflectionClass($bean);
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
                } catch (\ReflectionException $e) {
                    trigger_error('Cannot assign attributes to bean: '.$e->getMessage());
                }
            }
        }

        return $bean;
    }

    /**
     * @param object $bean
     * @param bool   $includeGetters
     * @param bool   $uncamelizeKey
     * @param bool   $recursive
     */
    public static function toArray($bean, $includeGetters = true, $uncamelizeKey = false, $recursive = false): array
    {
        if (null === $bean || !is_object($bean)) {
            throw new \InvalidArgumentException("Parameter 'bean' need be an object, got ".gettype($bean));
        }
        $properties = get_object_vars($bean);
        if ($includeGetters) {
            try {
                $class = new \ReflectionClass($bean);
                foreach ($class->getMethods() as $method) {
                    if ($method->isStatic() || !$method->isPublic()) {
                        continue;
                    }
                    if (0 === $method->getNumberOfParameters()
                        && preg_match('/^(get|is|has)(.+)/', $method->getName(), $matches)) {
                        $properties[lcfirst($matches[2])] = $method->invoke($bean);
                    }
                }
            } catch (\ReflectionException $e) {
                trigger_error('Cannot convert bean to array: '.$e->getMessage());
            }
        }
        if ($uncamelizeKey) {
            $properties = self::snakeCaseKeys($properties);
        }
        if ($recursive) {
            $properties = self::recursiveToArray($properties, $includeGetters, $uncamelizeKey);
        }

        return $properties;
    }

    private static function recursiveToArray(array $values, $includeGetters, $uncamelizeKey): array
    {
        foreach ($values as $key => $val) {
            if (is_object($val)) {
                $values[$key] = self::toArray($val, $includeGetters, $uncamelizeKey, true);
            } elseif (is_array($val)) {
                $values[$key] = self::recursiveToArray($val, $includeGetters, $uncamelizeKey);
            }
        }

        return $values;
    }

    /**
     * Merges two or more arrays into one recursively.
     * If each array has an element with the same string key value, the latter
     * will overwrite the former (different from array_merge_recursive).
     * Recursive merging will be conducted if both arrays have an element of array
     * type and are having the same key.
     * For integer-keyed elements, the elements from the latter array will
     * be appended to the former array.
     *
     * Borrow from yii\helper\ArrayHelper
     *
     * @param array $args arrays to be merged
     *
     * @return array the merged array (the original arrays are not changed.)
     */
    public static function merge(...$args): array
    {
        $res = array_shift($args);
        while (!empty($args)) {
            $next = array_shift($args);
            foreach ($next as $k => $v) {
                if (is_int($k)) {
                    if (isset($res[$k])) {
                        $res[] = $v;
                    } else {
                        $res[$k] = $v;
                    }
                } elseif (is_array($v) && isset($res[$k]) && is_array($res[$k])) {
                    $res[$k] = self::merge($res[$k], $v);
                } else {
                    $res[$k] = $v;
                }
            }
        }

        return $res;
    }

    /**
     * Applies the callback to the keys of given array.
     */
    public static function mapKeys(array $arr, callable $callback): array
    {
        $result = [];
        array_walk($arr, static function (&$value, $key) use (&$result, $callback) {
            $result[$callback($key)] = $value;
        });

        return $result;
    }

    /**
     * @deprecated {@see snakeCaseKeys}
     */
    public static function uncamelizeKeys(array $arr): array
    {
        return self::snakeCaseKeys($arr);
    }

    public static function snakeCaseKeys(array $arr): array
    {
        return self::mapKeys($arr, [Text::class, 'snakeCase']);
    }
}
