<?php

declare(strict_types=1);

namespace kuiper\helper;

/**
 * Access array use key separated by dot(.) :.
 *
 *     $array = Properties::fromArray([
 *          'redis' => [
 *              'host' => 'localhost'
 *          ]
 *     ]);
 *     echo $array->get('redis.host');   // 'localhost'
 */
class Properties extends \ArrayIterator implements PropertyResolverInterface
{
    public function __get($name)
    {
        return $this[$name] ?? null;
    }

    public function __set($name, $value)
    {
        throw new \BadMethodCallException('Cannot modify config');
    }

    public function __isset($name)
    {
        return isset($this[$name]);
    }

    /**
     * {@inheritdoc}
     */
    public function has(string $key): bool
    {
        return null !== $this->getValue($key);
    }

    /**
     * {@inheritdoc}
     */
    public function get(string $key, $default = null)
    {
        $value = $this->getValue($key);
        if ($value instanceof self) {
            return $value->toArray();
        }

        return $value ?? $default;
    }

    private function getValue(string $key)
    {
        $pos = strpos($key, '.');
        $posBracket = strpos($key, '[');
        if (false === $pos && false === $posBracket) {
            return $this[$key] ?? null;
        }
        if (false === $pos || (false !== $posBracket && $posBracket < $pos)) {
            if (0 === $posBracket) {
                $posRight = strpos($key, ']');
                if (false === $posRight) {
                    throw new \InvalidArgumentException("invalid key $key");
                }
                $current = substr($key, 1, $posRight - 1);
                $rest = substr($key, $posRight + 1);
                if ($rest && 0 === strpos($rest, '.')) {
                    $rest = substr($rest, 1);
                }
            } else {
                $current = substr($key, 0, $posBracket);
                $rest = substr($key, $posBracket);
            }
        } else {
            $current = substr($key, 0, $pos);
            $rest = substr($key, $pos + 1);
        }
        // var_export([$pos, $posBracket, $current, $key, $rest]);
        if (isset($this[$current])) {
            if (empty($rest)) {
                return $this[$current] ?? null;
            }

            if ($this[$current] instanceof self) {
                return $this[$current]->getValue($rest);
            }
        }

        return null;
    }

    public function merge(array $configArray, $append = true): void
    {
        foreach ($configArray as $key => $value) {
            if (!isset($value)) {
                unset($this[$key]);
                continue;
            }
            if (!is_array($value) || !isset($this[$key]) || !$this[$key] instanceof self) {
                $this[$key] = $this->createItem($value);
            } elseif (isset($value[0])) {
                if ($append) {
                    foreach ($value as $item) {
                        $this[$key]->append($this->createItem($item));
                    }
                } else {
                    $this[$key] = $this->createItem($value);
                }
            } else {
                $this[$key]->merge($value, $append);
            }
        }
    }

    public function toArray(): array
    {
        $result = [];
        foreach ($this as $key => $value) {
            if ($value instanceof self) {
                $result[$key] = $value->toArray();
            } else {
                $result[$key] = $value;
            }
        }

        return $result;
    }

    public static function fromArray(array $configArray): self
    {
        $config = new static();
        foreach ($configArray as $key => $value) {
            if (is_array($value)) {
                $config[$key] = static::fromArray($value);
            } else {
                $config[$key] = $value;
            }
        }

        return $config;
    }

    /**
     * @param mixed $value
     *
     * @return static|array
     */
    private function createItem($value)
    {
        return is_array($value) ? static::fromArray($value) : $value;
    }
}
