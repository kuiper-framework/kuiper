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

use InvalidArgumentException;
use kuiper\cache\ArrayCache;
use Redis;

class RedisDriver extends AbstractDriver
{
    public const GROUP_KEY_PREFIX = 'group.';

    private Redis $redis;

    private string $prefix = '';
    private ArrayCache $keyCache;

    protected function setOptions(array $options = []): void
    {
        if (!isset($options['redis'])) {
            throw new InvalidArgumentException('redis is required');
        }
        $this->redis = $options['redis'];
        $this->prefix = $options['prefix'] ?? '';
        $this->keyCache = new ArrayCache(100);
    }

    public function getData(string $key): array
    {
        $data = $this->redis->get($this->makeKeyString($key));
        if (false === $data) {
            return [];
        }

        return unserialize($data, ['allowed_classes' => true]);
    }

    public function storeData(string $key, mixed $data, int $expiration): bool
    {
        $store = serialize(['data' => $data, 'expiration' => $expiration]);
        if (is_null($expiration)) {
            return $this->redis->set($this->makeKeyString($key), $store);
        } else {
            $ttl = $expiration - time();

            // Prevent us from even passing a negative ttl'd item to redis,
            // since it will just round up to zero and cache forever.
            if ($ttl < 1) {
                return true;
            }

            return $this->redis->setex($this->makeKeyString($key), $ttl, $store);
        }
    }

    public function clear(string $key = null): bool
    {
        if (is_null($key)) {
            return true;
        }

        $redis = $this->redis;
        $keyReal = $this->makeKeyString($key);
        $redis->del($keyReal); // remove direct item.
        if ($this->isGroupKey($key)) {
            $keyString = $this->makeKeyString($key, true);
            $this->keyCache->delete($keyString);
            $redis->incr($keyString); // increment index for children items
        }

        return true;
    }

    public function purge(): bool
    {
        return true;
    }

    /**
     * 优化：对于一级 key 不做索引。如果需要使用级联删除，必须使用多级路径.
     *
     * Turns a key array into a key string. This includes running the indexing functions used to manage the Redis
     * hierarchical storage.
     *
     * When requested the actual path, rather than a normalized value, is returned.
     */
    protected function makeKeyString(string $key, bool $path = false): string
    {
        $keyString = 'cache:::';
        $pathKey = ':pathdb::';
        $nodeList = self::normalizeKeys($key);
        if (!$path && !$this->isGroupKey($key)) {
            return $this->prefix.md5($nodeList[0]);
        }

        $redis = $this->redis;
        foreach ($nodeList as $i => $name) {
            // a. cache:::name
            // b. cache:::name0:::sub
            $keyString .= $name;

            // a. :pathdb::cache:::name
            // b. :pathdb::cache:::name0:::sub
            $pathKey = ':pathdb::'.$keyString;
            $pathKey = $this->prefix.md5($pathKey);

            if ($this->keyCache->has($pathKey)) {
                $index = $this->keyCache->get($pathKey);
            } else {
                $index = $redis->get($pathKey);
                $this->keyCache->set($pathKey, $index);
            }

            // a. cache:::name0:::
            // b. cache:::name0:::sub1:::
            $keyString .= '_'.$index.':::';
        }

        return $path ? $pathKey : $this->prefix.md5($keyString);
    }

    public static function normalizeKeys($key, $hash = 'md5')
    {
        $keys = explode('/', $key);
        $pKey = [];
        foreach ($keys as $keyPiece) {
            if ('' === $keyPiece) {
                continue;
            }
            $prefix = str_starts_with($keyPiece, '@') ? '@' : '';
            $pKeyPiece = $prefix.$hash($keyPiece);
            $pKey[] = $pKeyPiece;
        }

        return $pKey;
    }

    private function isGroupKey(string $key): bool
    {
        return str_starts_with($key, self::GROUP_KEY_PREFIX);
    }
}
