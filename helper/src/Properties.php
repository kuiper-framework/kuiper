<?php

namespace kuiper\helper;

/**
 * Access array use key separated by dot(.) :.
 *
 *     $array = new Properties([
 *          'redis' => [
 *              'host' => 'localhost'
 *          ]
 *     ]);
 *     echo $array->get('redis.host');   // 'localhost'
 */
class Properties extends \ArrayIterator
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

    public function has(string $key): bool
    {
        return null !== $this->get($key);
    }

    public function get(string $key, $default = null)
    {
        $pos = strpos($key, '.');
        $posBracket = strpos($key, '[');
        if (false === $pos && false === $posBracket) {
            return $this[$key] ?? $default;
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
                return $this[$current];
            }

            if ($this[$current] instanceof  self) {
                return $this[$current]->get($rest, $default);
            }
        }

        return $default;
    }

    public function merge(array $configArray): void
    {
        foreach ($configArray as $key => $value) {
            if (isset($this[$key]) && is_array($value) && $this[$key] instanceof self) {
                $this[$key]->merge($value);
                continue;
            }
            $this[$key] = is_array($value) ? static::fromArray($value) : $value;
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
}
