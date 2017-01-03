<?php

namespace kuiper\cache;

use InvalidArgumentException;
use kuiper\cache\driver\DriverInterface;
use Psr\Cache\CacheItemInterface;

class Pool implements PoolInterface
{
    /**
     * @var DriverInterface
     */
    protected $driver;

    /**
     * @var string
     */
    protected $itemClass = Item::class;

    /**
     * @var array
     */
    protected $options = [
        'namespace_separator' => '/',
        'lifetime' => null,
        'precompute_time' => null,
        'prefix' => null,
        'lock_ttl' => null,
    ];

    public function __construct(DriverInterface $driver, array $options = [])
    {
        $this->driver = $driver;
        if (!empty($options)) {
            if (isset($options['namespace_separator'])
                && strlen($options['namespace_separator']) !== 1) {
                throw new InvalidArgumentException(sprintf(
                    "Option 'namespace_separator' should be a charator, Got '%s'",
                    $options['namespace_separator']
                ));
            }
            $this->options = array_merge($this->options, $options);
        }
        $this->driver->setPrefix($this->getOption('prefix'));
    }

    /**
     * {@inheritdoc}
     */
    public function setItemClass($class)
    {
        if (!class_exists($class)) {
            throw new InvalidArgumentException("Item class '$class' does not exist");
        }
        if (!is_a($class, ItemInterface::class, true)) {
            throw new InvalidArgumentException("Item class '$class' must inherit from ".ItemInterface::class);
        }
        $this->itemClass = $class;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getDriver()
    {
        return $this->driver;
    }

    /**
     * {@inheritdoc}
     */
    public function getOption($name)
    {
        return isset($this->options[$name]) ? $this->options[$name] : null;
    }

    /**
     * {@inheritdoc}
     */
    public function remember($key, callable $resolver, array $options = [])
    {
        $item = $this->getItem($key);
        if (array_key_exists('precompute_time', $options)) {
            $precomputeTime = $options['precompute_time'];
        } else {
            $precomputeTime = $this->getOption('precompute_time');
        }
        if (isset($precomputeTime)) {
            $lock_ttl = isset($options['lock_ttl']) ? $options['lock_ttl'] : $this->getOption('lock_ttl');
            $item->setPrecomputeTime($precomputeTime, $lock_ttl);
        }
        if (!$item->isHit()) {
            $lifetime = isset($options['lifetime']) ? $options['lifetime'] : $this->getOption('lifetime');
            $item->expiresAfter($lifetime);
            try {
                $value = $resolver();
            } catch (\Exception $e) {
                $item->unlock();
                throw $e;
            }
            $this->save($item->set($value));
        }

        return $item->get();
    }

    /**
     * {@inheritdoc}
     */
    public function getItem($key)
    {
        $item = $this->createItem($key);
        $value = $this->driver->get($item->getKeyPath());

        return $this->setItemValue($item, $value);
    }

    /**
     * {@inheritdoc}
     */
    public function getItems(array $keys = [])
    {
        $keys = array_values($keys);
        $items = [];
        $paths = [];
        foreach ($keys as $key) {
            $item = $this->createItem($key);
            $items[] = $item;
            $paths[] = $item->getKeyPath();
        }
        foreach ($this->driver->mget($paths) as $i => $value) {
            $this->setItemValue($items[$i], $value);
        }
        $results = [];
        foreach ($items as $item) {
            $results[$item->getKey()] = $item;
        }

        return $results;
    }

    /**
     * {@inheritdoc}
     */
    public function hasItem($key)
    {
        return $this->getItem($key)->isHit();
    }

    /**
     * {@inheritdoc}
     */
    public function clear()
    {
        return $this->driver->clear();
    }

    /**
     * {@inheritdoc}
     */
    public function deleteItem($key)
    {
        $path = $this->createItem($key)->getKeyPath();
        $delim = $this->getOption('namespace_separator');
        if (substr($key, -1) === $delim) {
            $path[] = null;
        }

        return $this->driver->del($path);
    }

    /**
     * {@inheritdoc}
     */
    public function deleteItems(array $keys)
    {
        $success = true;
        foreach ($keys as $key) {
            if (!$this->deleteItem($key)) {
                $success = false;
            }
        }

        return $success;
    }

    /**
     * {@inheritdoc}
     */
    public function save(CacheItemInterface $item)
    {
        return $item->save();
    }

    /**
     * {@inheritdoc}
     */
    public function saveDeferred(CacheItemInterface $item)
    {
        return $this->save($item);
    }

    /**
     * {@inheritdoc}
     */
    public function commit()
    {
        return true;
    }

    private function createItem($key)
    {
        return $item = new $this->itemClass($this, $key);
    }

    private function setItemValue(ItemInterface $item, $value)
    {
        if ($value === false) {
            $expires = $this->getOption('lifetime');
            if ($expires > 0) {
                $item->expiresAfter($expires);
            }
            $item->miss();
        } else {
            $item->set($value['data']);
            $item->expiresAt(isset($value['expiration']) ? $value['expiration'] : -1);
        }

        return $item;
    }
}
