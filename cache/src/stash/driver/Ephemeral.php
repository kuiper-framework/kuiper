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

namespace kuiper\cache\stash\driver;

class Ephemeral extends AbstractDriver
{
    private array $store = [];

    private int $maxItems = 256;

    /**
     * @var callable
     */
    private $timeFactory;

    private bool $serialized = true;

    private int $maxLifetime = 0;

    private float $fillRate = 0.6;

    protected function setOptions(array $options = []): void
    {
        if (isset($options['maxItems'])) {
            $this->maxItems = (int) $options['maxItems'];
        }
        if (isset($options['serialized'])) {
            $this->serialized = (bool) $options['serialized'];
        }
        if (isset($options['fillRate'])) {
            $this->fillRate = (float) $options['fillRate'];
        }
        if (isset($options['maxLifetime'])) {
            $this->maxLifetime = (int) $options['maxLifetime'];
        }
        if (isset($options['timeFactory'])) {
            $this->timeFactory = $options['timeFactory'];
        } else {
            $this->timeFactory = 'time';
        }
    }

    public function getData(string $key): array
    {
        if (!isset($this->store[$key])) {
            return [];
        }
        $data = $this->store[$key];
        if ($this->serialized && isset($data['data'])) {
            $data['data'] = unserialize($data['data'], ['allow_classes' => true]);
        }
        $now = $this->currentTime();
        if (isset($data['expiration']) && $data['expiration'] > $now) {
            return $data;
        }

        return [];
    }

    public function storeData(string $key, mixed $data, int $expiration): bool
    {
        if ($this->maxItems > 0 && count($this->store) >= $this->maxItems) {
            $this->purge();
        }
        if ($this->serialized) {
            $data = serialize($data);
        }
        if ($this->maxLifetime > 0) {
            $expiration = min($expiration, $this->currentTime() + $this->maxLifetime);
        }

        $this->store[$key] = ['data' => $data, 'expiration' => $expiration];

        return true;
    }

    public function clear(string $key = null)
    {
        if (!isset($key)) {
            $this->store = [];
        } else {
            foreach ($this->store as $k => $v) {
                if (str_starts_with($k, $key)) {
                    unset($this->store[$k]);
                }
            }
        }
    }

    private function currentTime(): int
    {
        return call_user_func($this->timeFactory);
    }

    public function purge()
    {
        $count = count($this->store);
        $now = $this->currentTime();
        foreach ($this->store as $itemKey => $item) {
            if ($item['expiration'] < $now) {
                unset($this->store[$itemKey]);
                --$count;
            }
        }

        if ($count > $this->maxItems) {
            $this->store = array_slice($this->store, -1 * (int) ($this->maxItems * $this->fillRate));
        }
    }
}
