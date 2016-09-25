<?php
namespace kuiper\cache;

use DateTime;
use DateTimeInterface;
use DateInterval;
use InvalidArgumentException;
use RuntimeException;

class Item implements ItemInterface
{
    /**
     * @var PoolInterface
     */
    protected $pool;
    
    /**
     * @var array
     */
    protected $path;

    /**
     * @var mixed
     */
    protected $value;

    /**
     * @var int
     */
    protected $expiration;

    /**
     * @var int
     */
    protected $precomputeTime;

    /**
     * @var bool
     */
    protected $locked;

    /**
     * @var bool
     */
    protected $isHit;
    
    /**
     * @inheritDoc
     */
    public function setPool(PoolInterface $pool)
    {
        $this->pool = $pool;
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function setKey($key)
    {
        $this->assertPoolAvailable(__METHOD__);
        if ($this->pool === null) {
            throw new RuntimeException("Pool is required, call method 'setPool' before " . __METHOD__);
        }
        $delim = $this->pool->getOption('namespace_separator');
        $trimed = trim($key, $delim);
        if (empty($trimed)) {
            if (empty($key)) {
                throw new InvalidArgumentException("Cache key '$key' must be non-empty");
            } else {
                throw new InvalidArgumentException("Invalid cache key '$key'");
            }
        }
        $path = explode($delim, $trimed);
        foreach ($path as $node) {
            if (empty($node)) {
                throw new InvalidArgumentException("Invalid cache key '$key'");
            }
        }
        $prefix = $this->pool->getOption('prefix');
        if (isset($prefix)) {
            $path[0] = $prefix . $path[0];
        }
        $this->path = $path;
        $this->key = $key;
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getKeyPath()
    {
        return $this->path;
    }

    /**
     * @inheritDoc
     */
    public function getKey()
    {
        return $this->key;
    }

    /**
     * @inheritDoc
     */
    public function getExpiration()
    {
        return $this->expiration;
    }

    /**
     * @inheritDoc
     */
    public function setPrecomputeTime($seconds)
    {
        $this->precomputeTime = $seconds;
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function save()
    {
        $driver = $this->pool->getDriver();
        $success = $driver->set(
            $this->getKeyPath(),
            $this->get(),
            $this->getExpiration()
        );
        $this->unlock();
        return $success;
    }

    /**
     * @inheritDoc
     */
    public function clear()
    {
        return $this->pool->deleteItem($this->getKey());
    }

    /**
     * @inheritDoc
     */
    public function unlock()
    {
        if ($this->locked) {
            return $this->pool->getDriver()->unlock($this->getKeyPath());
        }
        return true;
    }
    
    /**
     * @inheritDoc
     */
    public function get()
    {
        return $this->value;
    }

    /**
     * @inheritDoc
     */
    public function isHit()
    {
        if ($this->isHit === null) {
            $this->isHit = $this->getIsHit();
        }
        return $this->isHit;
    }

    /**
     * @inheritDoc
     */
    public function miss()
    {
        $this->isHit = false;
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function set($value)
    {
        $this->value = $value;
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function expiresAt($expiration)
    {
        $this->expiration = $expiration instanceof DateTimeInterface
                         ? $expiration->getTimestamp()
                         : $expiration;
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function expiresAfter($time)
    {
        $this->expiration = $time instanceof DateInterval
                         ? (new DateTime())->add($time)->getTimestamp()
                         : time() + $time;
        return $this;
    }

    private function assertPoolAvailable($method)
    {
        if ($this->pool === null) {
            throw new RuntimeException("Pool is required, call method 'setPool' before calling '$method'");
        }
    }

    private function getIsHit()
    {
        if (isset($this->expiration)) {
            if ($this->expiration <= 0) {
                return true;
            }
            $ttl = $this->expiration - time();
            if ($ttl < 0) {
                return false;
            }
            $precomputeTime = $this->precomputeTime;
            if (!isset($precomputeTime)) {
                $precomputeTime = $this->pool->getOption('precompute_time');
            }
            if (isset($precomputeTime) && $ttl <= $precomputeTime) {
                if ($this->pool->getDriver()->lock($this->getKeyPath(), $ttl)) {
                    $this->locked = true;
                    return false;
                }
            }
            return true;
        }
        return false;
    }
}
