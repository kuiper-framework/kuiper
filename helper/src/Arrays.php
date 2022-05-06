<?php

/*
 * This file is part of the Kuiper package.
 *
 * (c) Ye Wenbin <wenbinye@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace kuiper\helper;

use ArrayAccess;
use InvalidArgumentException;
use Iterator;
use ReflectionClass;
use ReflectionException;

class Arrays
{
    public static function isEmpty(\Countable|array|null $arr): bool
    {
        return !isset($arr) || 0 === count($arr);
    }

    public static function isNotEmpty(\Countable|array|null $arr): bool
    {
        return isset($arr) && count($arr) > 0;
    }

    /**
     * Collects value from array.
     *
     * @param array|Iterator $arr
     * @param string|int     $name
     *
     * @return array
     */
    public static function pull(iterable $arr, string|int $name): array
    {
        $ret = [];
        foreach ($arr as $elem) {
            if (is_object($elem)) {
                $method = 'get'.$name;
                /* @phpstan-ignore-next-line */
                $ret[] = $elem->$method();
            } else {
                $ret[] = $elem[$name] ?? null;
            }
        }

        return $ret;
    }

    /**
     * @param array|Iterator $arr
     */
    public static function pullField(iterable $arr, string $name): array
    {
        $ret = [];
        foreach ($arr as $elem) {
            /* @phpstan-ignore-next-line */
            $ret[] = $elem->$name;
        }

        return $ret;
    }

    /**
     * Creates associated array.
     *
     * @param array|Iterator  $arr
     * @param callable|string $name
     *
     * @return array
     */
    public static function assoc(iterable $arr, callable|string $name): array
    {
        $ret = [];

        foreach ($arr as $elem) {
            if (is_callable($name)) {
                $ret[$name($elem)] = $elem;
            } elseif (is_object($elem)) {
                $method = 'get'.$name;
                /* @phpstan-ignore-next-line */
                $ret[$elem->$method()] = $elem;
            } else {
                $ret[$elem[$name] ?? null] = $elem;
            }
        }

        return $ret;
    }

    /**
     * @param array|Iterator $arr
     */
    public static function assocByField(iterable $arr, string $name): array
    {
        return self::assoc($arr, static function ($item) use ($name) {
            /* @phpstan-ignore-next-line */
            return $item->$name;
        });
    }

    public static function toMap(iterable $arr, callable|string $name): array
    {
        return self::assoc($arr, $name);
    }

    public static function groupBy(iterable $arr, callable|string $groupBy): array
    {
        $ret = [];
        foreach ($arr as $elem) {
            if (is_callable($groupBy)) {
                $key = $groupBy($elem);
            } elseif (is_object($elem)) {
                $method = 'get'.$groupBy;
                /** @phpstan-ignore-next-line */
                $key = $elem->$method();
            } else {
                $key = $elem[$groupBy] ?? null;
            }
            if (null === $key || is_scalar($key)) {
                $ret[$key][] = $elem;
            } else {
                throw new InvalidArgumentException("Cannot group by key '$groupBy', support only scalar type, got ".get_debug_type($key));
            }
        }

        return $ret;
    }

    /**
     * @param array|Iterator $arr
     */
    public static function groupByField(iterable $arr, string $groupBy): array
    {
        $ret = [];
        foreach ($arr as $elem) {
            /** @phpstan-ignore-next-line */
            $key = $elem->$groupBy;
            if (null === $key || is_scalar($key)) {
                $ret[$key][] = $elem;
            } else {
                throw new InvalidArgumentException("Cannot group by key '$groupBy', support only scalar type, got ".(get_debug_type($key)));
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
                    throw new InvalidArgumentException('element type is not array');
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
     * @param mixed $arr
     * @param array $includedKeys
     *
     * @return array
     */
    public static function select(mixed $arr, array $includedKeys): array
    {
        $ret = [];
        if (is_object($arr)) {
            foreach ($includedKeys as $name) {
                $method = 'get'.$name;
                if (method_exists($arr, $method)) {
                    /* @phpstan-ignore-next-line */
                    $ret[$name] = $arr->$method();
                }
            }
        } elseif ($arr instanceof ArrayAccess) {
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
     * Create array with given keys.
     *
     * @param object|array $arr
     */
    public static function selectField(object|array $arr, array $includedKeys): array
    {
        $ret = [];
        foreach ($includedKeys as $name) {
            /* @phpstan-ignore-next-line */
            if (isset($arr->$name)) {
                /* @phpstan-ignore-next-line */
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
        return array_filter($arr, static function ($elem): bool {
            return isset($elem);
        });
    }

    public static function assign(object $bean, iterable $attributes, bool $onlyPublic = true): object
    {
        if ($bean instanceof ArrayAccess) {
            foreach ($attributes as $name => $val) {
                $bean[$name] = $val;
            }
        } else {
            $properties = get_object_vars($bean);
            $failed = [];
            foreach ($attributes as $name => $val) {
                if (array_key_exists($name, $properties)) {
                    /* @phpstan-ignore-next-line */
                    $bean->{$name} = $val;
                } elseif (method_exists($bean, $method = 'set'.Text::camelCase($name))) {
                    /* @phpstan-ignore-next-line */
                    $bean->$method($val);
                } elseif (!$onlyPublic) {
                    $failed[$name] = $val;
                }
            }
            if (!$onlyPublic && !empty($failed)) {
                try {
                    $class = new ReflectionClass($bean);
                    foreach ($failed as $name => $val) {
                        if (!$class->hasProperty($name)) {
                            continue;
                        }
                        $property = $class->getProperty($name);
                        $property->setValue($bean, $val);
                    }
                } catch (ReflectionException $e) {
                    trigger_error('Cannot assign attributes to bean: '.$e->getMessage());
                }
            }
        }

        return $bean;
    }

    public static function toArray(object $bean, bool $includeGetters = true, bool $snakeCaseKey = false, bool $recursive = false): array
    {
        $properties = get_object_vars($bean);
        if ($includeGetters) {
            try {
                $class = new ReflectionClass($bean);
                foreach ($class->getMethods() as $method) {
                    if ($method->isStatic() || !$method->isPublic()) {
                        continue;
                    }
                    if (0 === $method->getNumberOfParameters()
                        && preg_match('/^(get|is|has)(.+)/', $method->getName(), $matches)) {
                        $properties[lcfirst($matches[2])] = $method->invoke($bean);
                    }
                }
            } catch (ReflectionException $e) {
                trigger_error('Cannot convert bean to array: '.$e->getMessage());
            }
        }
        if ($snakeCaseKey) {
            $properties = self::snakeCaseKeys($properties);
        }
        if ($recursive) {
            $properties = self::recursiveToArray($properties, $includeGetters, $snakeCaseKey);
        }

        return $properties;
    }

    private static function recursiveToArray(array $values, bool $includeGetters, bool $snakeCaseKey): array
    {
        foreach ($values as $key => $val) {
            if (is_object($val)) {
                $values[$key] = self::toArray($val, $includeGetters, $snakeCaseKey, true);
            } elseif (is_array($val)) {
                $values[$key] = self::recursiveToArray($val, $includeGetters, $snakeCaseKey);
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
        foreach ($arr as $key => $value) {
            $result[$callback($key)] = $value;
        }

        return $result;
    }

    public static function snakeCaseKeys(array $arr): array
    {
        return self::mapKeys($arr, [Text::class, 'snakeCase']);
    }
}
