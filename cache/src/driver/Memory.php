<?php

namespace kuiper\cache\driver;

class Memory extends AbstractDriver implements DriverInterface
{
    /**
     * @var array
     */
    private $values = [];

    /**
     * @var array
     */
    private $locks = [];

    /**
     * {@inheritdoc}
     */
    protected function fetch($key)
    {
        return isset($this->values[$key]) ? unserialize($this->values[$key]) : false;
    }

    /**
     * {@inheritdoc}
     */
    protected function batchFetch(array $keys)
    {
        $values = [];
        foreach ($keys as $key) {
            $values[] = $this->fetch($key);
        }

        return $values;
    }

    /**
     * {@inheritdoc}
     */
    protected function store($key, $value, $ttl)
    {
        $this->values[$key] = serialize($value);

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function del(array $key)
    {
        return $this->deleteAll($this->makeKey($key));
    }

    /**
     * {@inheritdoc}
     */
    public function clear()
    {
        return $this->deleteAll(null);
    }

    /**
     * {@inheritdoc}
     */
    public function lock(array $key, $ttl)
    {
        $index = $this->makeKey($key);
        if (isset($this->locks[$index]) && $this->locks[$index] - time() > 0) {
            return false;
        }
        $this->locks[$index] = time() + $ttl;

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function unlock(array $key)
    {
        unset($this->locks[$this->makeKey($key)]);

        return true;
    }

    /**
     * Converts the key array into a passed function.
     *
     * @param array $key
     *
     * @return string
     */
    protected function makeKey(array $key)
    {
        $index = '';
        foreach ($key as $value) {
            if (isset($value)) {
                $index .= str_replace('#', '#:', $value).'#';
            }
        }

        return $index;
    }

    protected function deleteAll($prefix)
    {
        if (isset($prefix)) {
            foreach ($this->values as $index => $data) {
                if (strpos($index, $prefix) === 0) {
                    unset($this->values[$index]);
                }
            }
        } else {
            $this->values = [];
        }

        return true;
    }
}
