<?php
namespace kuiper\cache;

use Psr\Cache\CacheItemInterface;
use InvalidArgumentException;
use kuiper\cache\driver\Memory;
use kuiper\cache\driver\DriverInterface;

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
     * @var array<ItemInterface>
     */
    protected $deferredItems;

    /**
     * @var array
     */
    protected $options = [
        'namespace_separator' => '/',
        'lifetime' => null,
        'precompute_time' => null,
        'prefix' => null,
        'lock_ttl' => null
    ];

    public function __construct(DriverInterface $driver = null, array $options = [])
    {
        $this->setOptions($options);
        if (isset($driver)) {
            $this->setDriver($driver);
        } else {
            $this->driver = new Memory;
        }
    }

    /**
     * @inheritDoc
     */
    public function setItemClass($class)
    {
        if (!class_exists($class)) {
            throw new InvalidArgumentException("Item class '$class' does not exist");
        }
        if (!is_a($class, ItemInterface::class, true)) {
            throw new InvalidArgumentException("Item class '$class' must inherit from " . ItemInterface::class);
        }
        $this->itemClass = $class;
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function setDriver(DriverInterface $driver)
    {
        $this->driver = $driver;
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getDriver()
    {
        return $this->driver;
    }

    /**
     * @inheritDoc
     */
    public function setOptions(array $options)
    {
        if (isset($options['namespace_separator'])
            && strlen($options['namespace_separator']) !== 1) {
            throw new InvalidArgumentException(sprintf(
                "Option 'namespace_separator' should be a charator, Got '%s'",
                $options['namespace_separator']
            ));
        }
        $this->options = array_merge($this->options, $options);
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getOption($name)
    {
        return isset($this->options[$name])
            ? $this->options[$name]
            : null;
    }

    /**
     * @inheritDoc
     */
    public function remember($key, callable $resolver, array $options = [])
    {
        $item = $this->getItem($key);
        $precomputeTime = $this->getOption('precompute_time');
        if (array_key_exists('precompute_time', $options)) {
            $precomputeTime = $options['precompute_time'];
        }
        $item->setPrecomputeTime($precomputeTime);
        if (!$item->isHit()) {
            $item->expiresAfter(isset($options['lifetime'])
                                ? $options['lifetime']
                                : $this->getOption('lifetime'));
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
     * @inheritDoc
     */
    public function getItem($key)
    {
        $item = $this->createItem($key);
        $value = $this->driver->get($item->getKeyPath());
        $this->initItem($item, $value);
        return $item;
    }

    /**
     * @inheritDoc
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
            $this->initItem($items[$i], $value);
        }
        $results = [];
        foreach ($items as $item) {
            $results[$item->getKey()] = $item;
        }
        return $results;
    }

    /**
     * @inheritDoc
     */
    public function hasItem($key)
    {
        return $this->getItem($key)->isHit();
    }

    /**
     * @inheritDoc
     */
    public function clear()
    {
        return $this->driver->clear($this->getOption('prefix'));
    }

    /**
     * @inheritDoc
     */
    public function deleteItem($key)
    {
        $item = $this->createItem($key);
        $path = $item->getKeyPath();
        $delim = $this->getOption('namespace_separator');
        $len = strlen($key);
        if (substr($key, $len-1, 1) === $delim) {
            $path[] = null;
        }
        return $this->driver->del($path);
    }

    /**
     * @inheritDoc
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
     * @inheritDoc
     */
    public function save(CacheItemInterface $item)
    {
        return $item->save();
    }

    /**
     * @inheritDoc
     */
    public function saveDeferred(CacheItemInterface $item)
    {
        return $this->save($item);
    }

    /**
     * @inheritDoc
     */
    public function commit()
    {
        return true;
    }

    private function createItem($key)
    {
        $item = new $this->itemClass();
        $item->setPool($this);
        $item->setKey($key);
        return $item;
    }

    private function initItem($item, $value)
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
    }
}
