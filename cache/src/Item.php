<?php

namespace kuiper\cache;

use DateInterval;
use DateTime;
use DateTimeInterface;
use InvalidArgumentException;

class Item implements ItemInterface
{
    /**
     * @var PoolInterface
     */
    protected $pool;

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
     * @var int
     */
    protected $lockTtl;

    /**
     * @var bool
     */
    protected $locked;

    /**
     * @var bool
     */
    protected $isHit;

    /**
     * @var string
     */
    private $key;

    /**
     * @var array
     */
    private $path;

    /**
     * {@inheritdoc}
     */
    public function __construct(PoolInterface $pool, $key)
    {
        $this->pool = $pool;
        $this->key = $key;

        $delimiter = $this->pool->getOption('namespace_separator');
        $trimmed = trim($key, $delimiter);
        if (empty($trimmed)) {
            if (empty($key)) {
                throw new InvalidArgumentException("Cache key '$key' must be non-empty");
            } else {
                throw new InvalidArgumentException("Invalid cache key '$key'");
            }
        }
        $path = explode($delimiter, $trimmed);
        foreach ($path as $node) {
            if (empty($node)) {
                throw new InvalidArgumentException("Invalid cache key '$key'");
            }
        }
        $this->path = $path;
    }

    /**
     * {@inheritdoc}
     */
    public function getKey()
    {
        return $this->key;
    }

    /**
     * {@inheritdoc}
     */
    public function getExpiration()
    {
        return $this->expiration;
    }

    /**
     * {@inheritdoc}
     */
    public function setPrecomputeTime($seconds, $lock_ttl)
    {
        $this->precomputeTime = $seconds;
        $this->lockTtl = $lock_ttl;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function save()
    {
        $success = $this->pool->getDriver()->set(
            $this->path, $this->value, $this->expiration
        );
        $this->unlock();

        return $success;
    }

    /**
     * {@inheritdoc}
     */
    public function delete()
    {
        return $this->pool->getDriver()->del($this->path);
    }

    /**
     * {@inheritdoc}
     */
    public function get()
    {
        return $this->value;
    }

    /**
     * {@inheritdoc}
     */
    public function isHit()
    {
        if (null === $this->isHit) {
            $this->isHit = $this->getIsHit();
        }

        return $this->isHit;
    }

    /**
     * {@inheritdoc}
     */
    public function set($value)
    {
        $this->value = $value;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function expiresAt($expiration)
    {
        $this->expiration = $expiration instanceof DateTimeInterface
                         ? $expiration->getTimestamp()
                         : $expiration;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function expiresAfter($time)
    {
        $this->expiration = $time instanceof DateInterval
                         ? (new DateTime())->add($time)->getTimestamp()
                         : time() + $time;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getKeyPath()
    {
        return $this->path;
    }

    /**
     * {@inheritdoc}
     */
    public function unlock()
    {
        if ($this->locked) {
            if ($this->pool->getDriver()->unlock($this->path)) {
                $this->locked = false;

                return true;
            } else {
                return false;
            }
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function miss()
    {
        $this->isHit = false;

        return $this;
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
                $lockTtl = isset($this->lockTtl) ? $this->lockTtl : max($ttl, 1);
                if ($this->pool->getDriver()->lock($this->path, $lockTtl)) {
                    $this->locked = true;

                    return false;
                }
            }

            return true;
        }

        return false;
    }
}
