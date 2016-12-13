<?php
namespace kuiper\cache\driver;

use Redis as RedisClient;
use RedisArray;
use RedisException;
use RuntimeException;
use InvalidArgumentException;

/**
 * The redis driver for storing data on redis server
 */
class Redis implements DriverInterface
{
    private static $REDIS_ARRAY_OPTIONS = array(
        "previous",
        "function",
        "distributor",
        "index",
        "autorehash",
        "pconnect",
        "retry_interval",
        "lazy_connect",
        "connect_timeout",
    );

    /**
     * @var array
     */
    protected $options;

    /**
     * @var RedisClient
     */
    protected $connection;

    /**
     * @var array
     */
    protected $pathCache;

    /**
     * @param array $options options contains keys
     *  - servers an array each value may contain keys: host, port, index
     */
    public function __construct($options = [])
    {
        if (!extension_loaded('redis')) {
            throw new RuntimeException("extension 'redis' is required to use redis cache");
        }
        $this->options = $options;
    }

    public function setRedis(RedisClient $redis)
    {
        $this->connection = $redis;
        return $this;
    }

    public function getRedis()
    {
        return $this->getConnection();
    }

    public function getConnection()
    {
        if ($this->connection === null) {
            $this->connect();
        } elseif ($this->connection === false) {
            throw new RuntimeException("Redis connection has been disconnected");
        }
        return $this->connection;
    }

    public function disconnect()
    {
        if ($this->connection) {
            try {
                $this->connection->close();
            } catch (RedisException $e) {
                // if connection failed or stale
            }
            $this->connection = false;
        }
    }

    public function connect()
    {
        $servers = $this->parseServers();
        $options = $this->options;
        if (count($servers) === 1) {
            $server = $servers[0];
            $redis = new RedisClient;
            $redis->connect($server['host'], $server['port']);
            if (isset($server['index'])) {
                $redis->select($server['index']);
            }
        } else {
            $serverArray = [];
            $redisArrayOptions = [];
            foreach ($servers as $server) {
                $serverArray[] = $server['host'] .':' . $server['port'];
            }
            foreach (self::$REDIS_ARRAY_OPTIONS as $name) {
                if (array_key_exists($name, $options)) {
                    $redisArrayOptions[$name] = $options[$name];
                }
            }
            $redis = new RedisArray($serverArray, $redisArrayOptions);
        }
        $serializer = 'php';
        if (isset($options['serializer'])) {
            $serializer = $options['serializer'];
        }
        $value = constant(RedisClient::class . '::SERIALIZER_' . strtoupper($serializer));
        if ($value === null) {
            throw new InvalidArgumentException("Unknown redis serializer '{$serializer}'");
        }
        $redis->setOption(RedisClient::OPT_SERIALIZER, $value);
        if (isset($options['database'])) {
            $redis->select($options['database']);
        }
        $this->connection = $redis;
    }
    
    protected function parseServers()
    {
        if (isset($this->options['servers'])) {
            $servers = [];
            foreach ($this->options['servers'] as $server) {
                $port = 6379;
                if (is_string($server)) {
                    $host = $server;
                } elseif (isset($server['host'])) {
                    $host = $server['host'];
                } elseif (isset($server['server'])) {
                    $host = $server['server'];
                } elseif (isset($server[0])) {
                    $host = $server[0];
                    if (isset($server[1])) {
                        $port = $server[1];
                    }
                }
                $servers[] = [
                    'host' => $host,
                    'port' => isset($server['port']) ? (int) $server['port'] : $port,
                    'index' => isset($server['index']) ? $server['index'] : null
                ];
            }
        } else {
            $servers = [['host' => '127.0.0.1', 'port' => 6379]];
        }
        return $servers;
    }

    public function clearPathCache()
    {
        $this->pathCache = [];
    }
                          
    /**
     * @inheritDoc
     */
    public function get(array $key)
    {
        $cacheKey = $this->makeKey($key);
        // error_log("fetch $cacheKey origin=" .json_encode($key));
        return $this->getConnection()->get($cacheKey);
    }

    /**
     * @inheritDoc
     */
    public function mget(array $keys)
    {
        $realKeys = [];
        foreach ($keys as $key) {
            $realKeys[] = $this->makeKey($key);
        }
        return $this->getConnection()->mget($realKeys);
    }

    /**
     * @inheritDoc
     */
    public function set(array $key, $data, $expiration)
    {
        $value = [
            'data' => $data,
            'expiration' => $expiration
        ];
        if ($expiration === null || $expiration <= 0) {
            return $this->getConnection()->set($this->makeKey($key), $value);
        } else {
            $ttl = $expiration - time();
            if ($ttl < 1) {
                return true;
            }
            return $this->getConnection()->setex($this->makeKey($key), $ttl, $value);
        }
    }

    /**
     * @inheritDoc
     */
    public function del(array $key)
    {
        $redis = $this->getConnection();
        list($pathKey, $realKey) = $this->makeKey($key, true);
        $redis->del($realKey);
        // error_log("del $realKey origin=". json_encode($key));
        $last = end($key);
        if ($last === null) {
            $this->pathCache[$pathKey] = $redis->incr($pathKey);
        }
        return true;
    }

    /**
     * @return bool
     */
    public function clear($prefix)
    {
        return true;
    }

    /**
     * @inheritDoc
     */
    public function lock(array $key, $ttl)
    {
        $key = $this->makeLockKey($key);
        $redis = $this->getConnection();
        $success = $redis->setnx($key, 1);
        if ($success) {
            $redis->expire($key, $ttl);
        }
        return $success;
    }

    /**
     * @inheritDoc
     */
    public function unlock(array $key)
    {
        return $this->getConnection()->del($this->makeLockKey($key));
    }

    protected function makeKey($key, $path = false)
    {
        $first = array_shift($key);
        $realKey = '_cache::' . $first;
        $pathKey = null;
        $redis = $this->getConnection();
        while ($key) {
            $name = array_shift($key);
            $pathKey = md5('_path::' . $realKey);
            if (isset($name)) {
                if (isset($this->pathCache[$pathKey])) {
                    $cacheVersion = $this->pathCache[$pathKey];
                } else {
                    $cacheVersion = $redis->get($pathKey);
                    $this->pathCache[$pathKey] = $cacheVersion;
                }
                $realKey .= '_' . $cacheVersion . '::' . $name;
            }
        }
        $realKey = md5($realKey);
        return $path ? [$pathKey, $realKey] : $realKey;
    }

    protected function makeLockKey($key)
    {
        return md5('_lock:' . implode("#", $key));
    }
}
