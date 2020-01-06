<?php

namespace kuiper\cache\driver;

use Predis\ClientInterface;

/**
 * The redis driver for storing data on redis server.
 */
class Predis extends RedisDriver implements DriverInterface
{
    /**
     * @param ClientInterface $redis
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

        return null === $ret ? false : unserialize($ret);
    }

    /**
     * {@inheritdoc}
     */
    protected function batchFetch(array $keys)
    {
        $values = $this->getConnection()->mget($keys);
        $ret = [];
        foreach ($values as $value) {
            $ret[] = null === $value ? false : unserialize($value);
        }

        return $ret;
    }
}
