<?php

namespace kuiper\cache\driver;

class Composite implements DriverInterface
{
    /**
     * @var DriverInterface[]
     */
    protected $drivers;

    public function __construct(array $drivers)
    {
        $this->drivers = $drivers;
    }

    public function getDrivers()
    {
        return $this->drivers;
    }

    /**
     * {@inheritdoc}
     */
    public function setPrefix($prefix)
    {
        foreach ($this->drivers as $driver) {
            $driver->setPrefix($prefix);
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function get(array $key)
    {
        $failedDrivers = [];
        foreach ($this->drivers as $driver) {
            $value = $driver->get($key);
            if (false === $value) {
                $failedDrivers[] = $driver;
            } else {
                foreach (array_reverse($failedDrivers) as $failedDriver) {
                    /* @var DriverInterface $failedDriver */
                    $failedDriver->set($key, $value['data'], $value['expiration']);
                }

                return $value;
            }
        }

        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function mget(array $keys)
    {
        $failedDrivers = [];
        $indexes = array_keys($keys);
        $restKeys = $keys;
        $values = array_fill(0, count($keys), false);
        foreach ($this->drivers as $driver) {
            $restValues = $driver->mget($restKeys);
            $nextIndexes = [];
            $nextKeys = [];
            foreach ($restValues as $i => $value) {
                $index = $indexes[$i];
                $key = $restKeys[$i];
                if (false === $value) {
                    $failedDrivers[$index][] = $driver;
                    $nextIndexes[] = $index;
                    $nextKeys[] = $key;
                } else {
                    $values[$index] = $value;
                    if (isset($failedDrivers[$index])) {
                        foreach (array_reverse($failedDrivers[$index]) as $failedDriver) {
                            /* @var DriverInterface $failedDriver */
                            $failedDriver->set($key, $value['data'], $value['expiration']);
                        }
                    }
                }
            }
            if (empty($nextKeys)) {
                break;
            }
            $restKeys = $nextKeys;
            $indexes = $nextIndexes;
        }

        return $values;
    }

    /**
     * {@inheritdoc}
     */
    public function set(array $key, $data, $expiration)
    {
        return $this->executeOnAll(function (DriverInterface $driver) use ($key, $data, $expiration) {
            return $driver->set($key, $data, $expiration);
        });
    }

    /**
     * {@inheritdoc}
     */
    public function del(array $key)
    {
        return $this->executeOnAll(function (DriverInterface $driver) use ($key) {
            return $driver->del($key);
        });
    }

    /**
     * {@inheritdoc}
     */
    public function clear()
    {
        return $this->executeOnAll(function (DriverInterface $driver) {
            return $driver->clear();
        });
    }

    /**
     * {@inheritdoc}
     */
    public function lock(array $key, $ttl)
    {
        $driver = end($this->drivers);

        return $driver->lock($key, $ttl);
    }

    /**
     * {@inheritdoc}
     */
    public function unlock(array $key)
    {
        $driver = end($this->drivers);

        return $driver->unlock($key);
    }

    protected function executeOnAll(callable $action)
    {
        $success = true;
        foreach (array_reverse($this->drivers) as $driver) {
            if (!$action($driver)) {
                $success = false;
            }
        }

        return $success;
    }
}
