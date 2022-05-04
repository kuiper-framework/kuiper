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

namespace kuiper\web\session;

use Psr\Cache\CacheItemPoolInterface;

/**
 * Use cache component as session storage.
 */
class CacheSessionHandler implements \SessionHandlerInterface, \SessionIdInterface
{
    /**
     * @var int
     */
    private readonly int $lifetime;

    /**
     * @var string cache prefix
     */
    private readonly string $prefix;

    public function __construct(
        private readonly CacheItemPoolInterface $cache,
        array   $options = [])
    {
        $this->prefix = $options['prefix'] ?? 'session_';
        $this->lifetime = $options['lifetime'] ?? 3600;
    }

    /**
     * {@inheritdoc}
     *
     * @SuppressWarnings("unused")
     */
    public function open(string $path, string $name): bool
    {
        return true;
    }

    /**
     * {@inheritdoc}
     *
     * @SuppressWarnings("CamelCaseMethodName")
     */
    public function create_sid(): string
    {
        $len = (int)ini_get('session.sid_length');
        if (empty($len)) {
            $len = 48;
        }
        $func = ini_get('session.hash_function');
        if (empty($func)) {
            $func = 'sha256';
        }

        return substr(hash($func, uniqid('session', true)), 0, $len);
    }

    /**
     * {@inheritdoc}
     */
    public function close(): bool
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function read(string $id): string|false
    {
        return $this->cache->getItem($this->prefix . $id)->get() ?? '';
    }

    /**
     * {@inheritdoc}
     */
    public function write(string $id, string $data): bool
    {
        $item = $this->cache->getItem($this->prefix . $id);
        $item->expiresAfter($this->lifetime);
        $this->cache->save($item->set($data));

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function destroy(string $id): bool
    {
        $this->cache->deleteItem($this->prefix . $id);

        return true;
    }

    /**
     * {@inheritdoc}
     *
     * @SuppressWarnings("ShortMethodName")
     */
    public function gc(int $max_lifetime): bool
    {
        return true;
    }
}
