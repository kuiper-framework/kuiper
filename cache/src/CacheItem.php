<?php

declare(strict_types=1);

namespace kuiper\cache;

use Stash\Invalidation;
use Stash\Item;

class CacheItem extends Item
{
    /**
     * @var bool
     */
    private $isSet = false;

    protected $invalidationMethod = Invalidation::NONE;

    public function setKey(array $key, $namespace = null): void
    {
        $this->namespace = $namespace;

        $keyStringTmp = $key;
        if (isset($this->namespace)) {
            array_shift($keyStringTmp);
        }

        $this->keyString = implode('/', $keyStringTmp);

        $this->key = array_map('strtolower', $key);
    }

    /**
     * 修复 set 后调用 isHit, isMiss 仍返回 false 问题.
     * {@inheritDoc}
     */
    public function set($value)
    {
        parent::set($value);
        $this->isSet = true;

        return $this;
    }

    /**
     * 修复 set 后调用 get 仍返回 null 问题
     * {@inheritdoc}
     *
     * @return mixed|null
     */
    public function get()
    {
        if ($this->isSet) {
            return $this->data;
        }

        return parent::get();
    }

    public function isMiss(): bool
    {
        if ($this->isSet) {
            return false;
        }

        return parent::isMiss();
    }

    protected function getStampedeFlag($key): bool
    {
        array_unshift($key, 'sp');

        return parent::getStampedeFlag($key);
    }
}
