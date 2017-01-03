<?php

namespace kuiper\cache\driver;

use Predis\ClientInterface;

/**
 * The redis driver for storing data on redis server.
 */
class Predis extends RedisDriver implements DriverInterface
{
    /**
     * @param array $options options contains keys
     *                       - servers an array each value may contain keys: host, port, index
     */
    public function __construct(ClientInterface $redis)
    {
        $this->connection = $redis;
    }

    /**
     * {@inheritdoc}
     */
    protected function fetch($key)
    {
        $ret = $this->getConnection()->get($key);

        return $ret === null ? false : unserialize($ret);
    }

    /**
     * {@inheritdoc}
     */
    protected function batchFetch(array $keys)
    {
        $values = $this->getConnection()->mget($keys);
        $ret = [];
        foreach ($values as $value) {
            $ret[] = $value === null ? false : unserialize($value);
        }

        return $ret;
    }
}
