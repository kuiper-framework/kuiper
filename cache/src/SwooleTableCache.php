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

namespace kuiper\cache;

use Psr\SimpleCache\CacheInterface;
use Swoole\Table;

class SwooleTableCache implements CacheInterface
{
    public const KEY_DATA = 'data';
    public const KEY_EXPIRES = 'expires';
    /**
     * @var Table
     */
    private $table;

    /**
     * @var int
     */
    private $ttl;

    /**
     * @param int $ttl
     * @param int $capacity number of entries to save
     * @param int $size     size for the data
     */
    public function __construct(int $ttl = 60, int $capacity = 1024, int $size = 512)
    {
        $this->table = new Table($capacity);
        $this->table->column(self::KEY_DATA, Table::TYPE_STRING, $size);
        $this->table->column(self::KEY_EXPIRES, Table::TYPE_INT, 4);
        $this->table->create();
        $this->ttl = $ttl;
    }

    /**
     * @return Table
     */
    public function getTable(): Table
    {
        return $this->table;
    }

    /**
     * @return int
     */
    public function getTtl(): int
    {
        return $this->ttl;
    }

    /**
     * {@inheritdoc}
     */
    public function get($key, $default = null)
    {
        $result = $this->table->get($key);
        if ($result && time() < $result[self::KEY_EXPIRES]) {
            return $this->unserialize($result[self::KEY_DATA]);
        }

        return $default;
    }

    /**
     * {@inheritdoc}
     */
    public function set($key, $value, $ttl = null)
    {
        $this->table->set($key, [
            self::KEY_DATA => $this->serialize($value),
            self::KEY_EXPIRES => time() + ($ttl ?? $this->ttl),
        ]);

        return true;
    }

    /**
     * @param mixed $value
     *
     * @return string
     */
    protected function serialize($value): string
    {
        return \serialize($value);
    }

    /**
     * @param string $data
     *
     * @return mixed
     */
    protected function unserialize(string $data)
    {
        return \unserialize($data, ['allowed_classes' => true]);
    }

    /**
     * {@inheritdoc}
     */
    public function delete($key)
    {
        $this->table->del($key);

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function clear()
    {
        $keys = [];
        foreach ($this->table as $key => $row) {
            $keys[] = $key;
        }
        $this->deleteMultiple($keys);

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
            $this->table->del($key);
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function has($key)
    {
        $expire = $this->table->get($key, self::KEY_EXPIRES);

        return isset($expire) && time() < $expire;
    }
}
