<?php

declare(strict_types=1);

namespace kuiper\web\session;

use Psr\Cache\CacheItemPoolInterface;

/**
 * Use cache component as session storage.
 */
class CacheSessionHandler implements \SessionHandlerInterface
{
    /**
     * @var CacheItemPoolInterface
     */
    private $cache;

    /**
     * @var int
     */
    private $lifetime = 3600;

    /**
     * @var string cache prefix
     */
    private $prefix = 'session_';

    public function __construct(CacheItemPoolInterface $cache, array $options = [])
    {
        $this->cache = $cache;
        if (isset($options['prefix'])) {
            $this->prefix = $options['prefix'];
        }

        if (isset($options['lifetime'])) {
            $this->lifetime = $options['lifetime'];
        }
    }

    /**
     * {@inheritdoc}
     *
     * @SuppressWarnings("unused")
     */
    public function open($savePath, $sessionName)
    {
        return true;
    }

    /**
     * {@inheritdoc}
     *
     * @SuppressWarnings("CamelCaseMethodName")
     */
    public function create_sid()
    {
        $len = (int) ini_get('session.sid_length');
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
    public function close()
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function read($sessionId)
    {
        return $this->cache->getItem($this->prefix.$sessionId)->get() ?: '';
    }

    /**
     * {@inheritdoc}
     */
    public function write($sessionId, $data)
    {
        $item = $this->cache->getItem($this->prefix.$sessionId);
        $item->expiresAfter($this->lifetime);
        $this->cache->save($item->set($data));

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function destroy($sessionId)
    {
        $this->cache->deleteItem($this->prefix.$sessionId);

        return true;
    }

    /**
     * {@inheritdoc}
     *
     * @SuppressWarnings("ShortMethodName")
     */
    public function gc($lifetime)
    {
        return true;
    }
}
