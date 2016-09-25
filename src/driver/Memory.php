<?php
namespace kuiper\cache\driver;

class Memory implements DriverInterface
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
     * Converts the key array into a passed function
     *
     * @param  array  $key
     * @return string
     */
    protected function getKeyIndex($key)
    {
        $index = '';
        foreach ($key as $value) {
            if (isset($value)) {
                $index .= str_replace('#', '#:', $value) . '#';
            }
        }

        return $index;
    }

    /**
     * @inheritDoc
     */
    public function get(array $key)
    {
        $index = $this->getKeyIndex($key);
        return isset($this->values[$index]) ? $this->values[$index] : false;
    }

    /**
     * @inheritDoc
     */
    public function mget(array $keys)
    {
        $values = [];
        foreach ($keys as $key) {
            $values[] = $this->get($key);
        }
        return $values;
    }

    /**
     * @inheritDoc
     */
    public function set(array $key, $data, $expiration)
    {
        $this->values[$this->getKeyIndex($key)] = [
            'data' => $data,
            'expiration' => $expiration
        ];
        return true;
    }

    /**
     * @inheritDoc
     */
    public function del(array $key)
    {
        return $this->clear($this->getKeyIndex($key));
    }

    /**
     * @inheritDoc
     */
    public function clear($prefix)
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

    /**
     * @inheritDoc
     */
    public function lock(array $key, $ttl)
    {
        $index = $this->getKeyIndex($key);
        if (isset($this->locks[$index]) && $this->locks[$index] - time() > 0) {
            return false;
        }
        $this->locks[$index] = time() + $ttl;
        return true;
    }

    /**
     * @inheritDoc
     */
    public function unlock(array $key)
    {
        unset($this->locks[$this->getKeyIndex($key)]);
        return true;
    }
}
