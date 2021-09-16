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

namespace kuiper\rpc\servicediscovery;

use Psr\SimpleCache\CacheInterface;

class InMemoryCache implements CacheInterface
{
    public const KEY_DATA = 'data';
    public const KEY_EXPIRES = 'expires';
    public const DEFAULT_TTL = 60;

    /**
     * @var array
     */
    private $table = [];

    /**
     * @var int
     */
    private $ttl;

    /**
     * InMemoryCache constructor.
     *
     * @param int $ttl
     */
    public function __construct(int $ttl = self::DEFAULT_TTL)
    {
        $this->ttl = $ttl;
    }

    /**
     * {@inheritdoc}
     */
    public function get($key, $default = null)
    {
        $result = $this->table[$key] ?? null;
        if (null !== $result && time() < $result[self::KEY_EXPIRES]) {
            return $result[self::KEY_DATA];
        }

        return $default;
    }

    /**
     * {@inheritdoc}
     */
    public function set($key, $value, $ttl = null)
    {
        $this->table[$key] = [
            self::KEY_DATA => $value,
            self::KEY_EXPIRES => time() + ($ttl ?? $this->ttl),
        ];

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function delete($key)
    {
        unset($this->table[$key]);

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function clear()
    {
        $this->table = [];

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function getMultiple($keys, $default = null)
    {
        $result = [];
        foreach ($keys as $key) {
            $result[$key] = $this->get($key, $default);
        }

        return $result;
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
        return null !== $this->get($key);
    }
}
