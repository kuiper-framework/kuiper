<?php

declare(strict_types=1);

namespace kuiper\rpc\servicediscovery;

use kuiper\rpc\transporter\Endpoint;
use Psr\SimpleCache\CacheInterface;
use Swoole\Table;

/**
 * 存储服务地址
 *  - routes 里记录的数据为 "tcp://172.16.0.1:10204?timeout=2\ntcp://172.16.0.1:10204?timeout=2".
 *
 * Class SwooleTableRegistryCache
 */
class SwooleTableServiceEndpointCache implements CacheInterface
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
     * SwooleTableRegistryCache constructor.
     *
     * @param int $ttl
     * @param int $capacity number of address to save
     * @param int $size     size for the address 每个服务长度大约50个字符，默认2046长度可以存储最多40台服务器的地址
     */
    public function __construct(int $ttl = 60, int $capacity = 256, int $size = 2046)
    {
        $this->table = new Table($capacity);
        $this->table->column(self::KEY_DATA, Table::TYPE_STRING, $size);
        $this->table->column(self::KEY_EXPIRES, Table::TYPE_INT, 4);
        $this->table->create();
        $this->ttl = $ttl;
    }

    /**
     * {@inheritdoc}
     */
    public function get($key, $default = null)
    {
        $result = $this->table->get($key);
        if ($result && time() < $result[self::KEY_EXPIRES]) {
            return $this->decode($result[self::KEY_DATA]);
        }

        return $default;
    }

    /**
     * {@inheritdoc}
     */
    public function set($key, $value, $ttl = null)
    {
        $this->table->set($key, [
            self::KEY_DATA => $this->encode($value),
            self::KEY_EXPIRES => time() + ($ttl ?? $this->ttl),
        ]);

        return true;
    }

    /**
     * @param Endpoint[] $endpoints
     *
     * @return string
     */
    private function encode(ServiceEndpoint $endpoint): string
    {
        return (string) $endpoint;
    }

    private function decode(string $data): ServiceEndpoint
    {
        return ServiceEndpoint::fromString($data);
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
