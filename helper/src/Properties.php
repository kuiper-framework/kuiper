<?php

declare(strict_types=1);

namespace kuiper\helper;

use function DI\create;

/**
 * Access array use key separated by dot(.) :.
 *
 *     $array = Properties::create([
 *          'redis' => [
 *              'host' => 'localhost'
 *          ]
 *     ]);
 *     echo $array->get('redis.host');   // 'localhost'
 */
final class Properties extends \ArrayIterator implements PropertyResolverInterface
{
    private const PLACEHOLDER_REGEXP = '#\{([^\{\}]+)\}#';

    private function __construct()
    {
        parent::__construct([]);
    }

    /**
     * @return mixed|null
     */
    public function __get(string $name)
    {
        return $this[$name] ?? null;
    }

    /**
     * @param mixed $value
     */
    public function __set(string $name, $value): void
    {
        throw new \BadMethodCallException('Cannot modify config');
    }

    public function __isset(string $name): bool
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

    /**
     * @param mixed $value
     */
    public function set(string $key, $value): void
    {
        $this->setValue($key, $value);
    }

    public function getInt(string $key, int $default = 0): int
    {
        return (int) $this->get($key, $default);
    }

    public function getBool(string $key, bool $default = false): bool
    {
        return (bool) $this->get($key, $default);
    }

    public function getString(string $key, string $default = ''): string
    {
        return (string) $this->get($key, $default);
    }

    public function getNumber(string $key, float $default = 0): float
    {
        return (float) $this->get($key, $default);
    }

    private function parseKey(string $key): array
    {
        $pos = strpos($key, '.');
        $posBracket = strpos($key, '[');
        if (false === $pos && false === $posBracket) {
            return [$key, null];
        }
        if (false === $pos || (false !== $posBracket && $posBracket < $pos)) {
            if (0 === $posBracket) {
                $posRight = strpos($key, ']');
                if (false === $posRight) {
                    throw new \InvalidArgumentException("invalid key $key");
                }
                $current = (int) substr($key, 1, $posRight - 1);
                $rest = substr($key, $posRight + 1);
                if ('' !== $rest && 0 === strpos($rest, '.')) {
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

        return [$current, $rest];
    }

    /**
     * @return mixed|null
     */
    private function getValue(string $key)
    {
        [$current, $rest] = $this->parseKey($key);
        if (!isset($this[$current])) {
            return null;
        }
        if (empty($rest)) {
            return $this[$current] ?? null;
        }

        if ($this[$current] instanceof self) {
            return $this[$current]->getValue($rest);
        }

        return null;
    }

    /**
     * @param mixed $value
     */
    private function setValue(string $key, $value, ?string $prefix = null): void
    {
        [$current, $rest] = $this->parseKey($key);
        if (empty($rest)) {
            $this[$current] = $this->createItem($value);

            return;
        }
        if (!isset($this[$current]) || !$this[$current] instanceof self) {
            $this[$current] = self::create();
        }
        $this[$current]->setValue($rest, $value, $prefix.(is_int($current) ? "[$current]" : $current.'.'));
    }

    public function merge(array $configArray, bool $append = true): void
    {
        foreach ($configArray as $key => $value) {
            if (!isset($value)) {
                if (isset($this[$key])) {
                    unset($this[$key]);
                }
                continue;
            }
            if (!is_array($value) || !isset($this[$key]) || !$this[$key] instanceof self) {
                $this[$key] = $this->createItem($value);
            } elseif ($this->isIndexBasedArray($value)) {
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

    public function mergeIfNotExists(array $configArray): void
    {
        foreach ($configArray as $key => $value) {
            if (is_array($value) && isset($this[$key]) && $this[$key] instanceof self) {
                $this[$key]->mergeIfNotExists($value);
            } elseif (!isset($this[$key])) {
                $this[$key] = $this->createItem($value);
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

    public static function create(array $arr = []): self
    {
        $config = new self();
        foreach ($arr as $key => $value) {
            if (is_array($value)) {
                $config[$key] = self::create($value);
            } else {
                $config[$key] = $value;
            }
        }

        return $config;
    }

    public function replacePlaceholder(): void
    {
        $this->replacePlaceholderRecursive($this, function (array $matches) {
            $name = $matches[1];
            if (!$this->has($name)) {
                throw new \RuntimeException("Unknown config entry: '$name'");
            }

            return $this->get($name);
        });
    }

    protected function replacePlaceholderRecursive(Properties $properties, callable $replacer): void
    {
        $re = self::PLACEHOLDER_REGEXP;
        foreach ($properties as $key => $value) {
            if (is_string($value) && preg_match(self::PLACEHOLDER_REGEXP, $value)) {
                do {
                    $value = preg_replace_callback($re, $replacer, $value);
                } while (preg_match(self::PLACEHOLDER_REGEXP, $value));

                $properties[$key] = $value;
            } elseif ($value instanceof self) {
                $this->replacePlaceholderRecursive($value, $replacer);
            }
        }
    }

    /**
     * @deprecated use {@link create()}
     */
    public static function fromArray(array $arr): self
    {
        return self::create($arr);
    }

    /**
     * @param mixed $value
     *
     * @return static|array
     */
    private function createItem($value)
    {
        return is_array($value) ? self::create($value) : $value;
    }

    private function isIndexBasedArray(array $value): bool
    {
        foreach (range(0, count($value) - 1) as $i) {
            if (!array_key_exists($i, $value)) {
                return false;
            }
        }

        return true;
    }
}
