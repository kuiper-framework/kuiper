<?php

/*
 * This file is part of the Kuiper package.
 *
 * (c) Ye Wenbin <wenbinye@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace kuiper\cache;

use Stash\Exception\InvalidArgumentException;
use Stash\Interfaces\ItemInterface;
use Stash\Pool;

class CacheItemPool extends Pool
{
    /**
     * @var int
     */
    private $defaultTtl = 432000;

    protected $itemClass = CacheItem::class;

    /**
     * {@inheritDoc}
     */
    public function getItem($key): ItemInterface
    {
        $keyString = trim($key, '/');
        $keyList = explode('/', $keyString);
        if (isset($this->namespace)) {
            array_unshift($keyList, $this->namespace);
        }

        foreach ($keyList as $node) {
            if ('' === $node) {
                throw new InvalidArgumentException('Invalid or Empty Node passed to getItem constructor.');
            }
        }

        /** @var ItemInterface $item */
        $item = new $this->itemClass();
        $item->setPool($this);
        $item->setKey($keyList);

        if ($this->isDisabled) {
            $item->disable();
        }

        if (isset($this->logger)) {
            /** @noinspection PhpParamsInspection */
            $item->setLogger($this->logger);
        }

        $item->expiresAfter($this->defaultTtl);

        return $item;
    }

    public function getDefaultTtl(): int
    {
        return $this->defaultTtl;
    }

    public function setDefaultTtl(int $defaultTtl): void
    {
        $this->defaultTtl = $defaultTtl;
    }
}
