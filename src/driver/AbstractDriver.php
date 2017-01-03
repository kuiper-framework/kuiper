<?php

namespace kuiper\cache\driver;

abstract class AbstractDriver
{
    /**
     * @var string
     */
    protected $prefix;

    public function setPrefix($prefix)
    {
        $this->prefix = $prefix;
    }

    /**
     * @param string $key
     *
     * @return mixed|false
     */
    abstract protected function fetch($key);

    /**
     * @param array $keys
     *
     * @return array
     */
    abstract protected function batchFetch(array $keys);

    /**
     * @param string $key
     * @param mixed  $value
     * @param int    $ttl
     *
     * @return bool
     */
    abstract protected function store($key, $value, $ttl);

    /**
     * @param array $path
     */
    abstract protected function makeKey(array $path);

    /**
     * {@inheritdoc}
     */
    public function get(array $path)
    {
        return $this->fetch($this->makeKey($path));
    }

    /**
     * {@inheritdoc}
     */
    public function mget(array $paths)
    {
        $keys = [];
        foreach ($paths as $i => $path) {
            $keys[] = $this->makeKey($path);
        }

        return $this->batchFetch($keys);
    }

    /**
     * {@inheritdoc}
     */
    public function set(array $path, $data, $expiration)
    {
        $value = [
            'data' => $data,
            'expiration' => $expiration,
        ];
        if ($expiration === null || $expiration <= 0) {
            $ttl = 0;
        } else {
            $ttl = $expiration - time();
            if ($ttl < 1) {
                return true;
            }
        }

        return $this->store($this->makeKey($path), $value, $ttl);
    }
}
