<?php

declare(strict_types=1);

namespace kuiper\cache;

use Psr\SimpleCache\CacheInterface;

/**
 * 存储服务地址
 *  - routes 里记录的数据为 "tcp -h 172.16.0.1 -t 20000 -p 10204 -e 0\ntcp -h 172.16.0.2 -t 20000 -p 10204 -e 0".
 *
 * Class SwooleTableRegistryCache
 */
class ArrayCache implements CacheInterface
{
    public const KEY_DATA = 0;
    public const KEY_EXPIRE = 1;
    /**
     * @var array
     */
    private $values;

    /**
     * @var int
     */
    private $ttl;

    /**
     * @var int
     */
    private $capacity;

    /**
     * SwooleTableRegistryCache constructor.
     *
     * @param int $ttl
     * @param int $capacity number of address to save
     */
    public function __construct(int $ttl = 60, int $capacity = 256)
    {
        $this->values = [];
        $this->capacity = $capacity;
        $this->ttl = $ttl;
    }

    /**
     * {@inheritdoc}
     */
    public function get($key, $default = null)
    {
        $result = $this->values[$key] ?? null;
        if (isset($result) && time() < $result[self::KEY_EXPIRE]) {
            return $result[self::KEY_DATA];
        }

        return $default;
    }

    /**
     * {@inheritdoc}
     */
    public function set($key, $value, $ttl = null)
    {
        $this->values[$key] = [
            self::KEY_DATA => $value,
            self::KEY_EXPIRE => time() + ($ttl ?? $this->ttl),
        ];
        while (count($this->values) > $this->capacity) {
            array_shift($this->values);
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function delete($key)
    {
        unset($this->values[$key]);

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function clear()
    {
        $this->values = [];

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function getMultiple($keys, $default = null)
    {
        $values = [];
        foreach ($keys as $key) {
            $values[] = $this->get($key, $default);
        }

        return $values;
    }

    /**
     * {@inheritdoc}
     */
    public function setMultiple($values, $ttl = null)
    {
        foreach ($values as $key => $value) {
            $this->set($key, $value, $ttl);
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function deleteMultiple($keys)
    {
        foreach ($keys as $key) {
            $this->delete($key);
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function has($key)
    {
        $result = $this->values[$key] ?? null;

        return isset($result) && time() < $result[self::KEY_EXPIRE];
    }
}
