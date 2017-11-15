<?php

namespace kuiper\cache\driver;

use InvalidArgumentException;
use Redis as RedisClient;
use RedisArray;
use RedisException;
use RuntimeException;

/**
 * The redis driver for storing data on redis server.
 */
class Redis extends RedisDriver implements DriverInterface
{
    private static $REDIS_ARRAY_OPTIONS = [
        'previous',
        'function',
        'distributor',
        'index',
        'autorehash',
        'pconnect',
        'retry_interval',
        'lazy_connect',
        'connect_timeout',
    ];

    /**
     * @var array
     */
    protected $options;

    /**
     * @param array $options options contains keys
     *                       - servers an array each value may contain keys: host, port, index
     *                       - serializer
     *                       - database
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
            throw new RuntimeException('Redis connection has been disconnected');
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
            $redis = new RedisClient();
            $redis->connect($server['host'], $server['port']);
            if (isset($server['index'])) {
                $redis->select($server['index']);
            }
        } else {
            $serverArray = [];
            $redisArrayOptions = [];
            foreach ($servers as $server) {
                $serverArray[] = $server['host'].':'.$server['port'];
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
        $value = constant(RedisClient::class.'::SERIALIZER_'.strtoupper($serializer));
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
                $host = 'localhost';
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
                    'index' => isset($server['index']) ? $server['index'] : null,
                ];
            }
        } else {
            $servers = [['host' => '127.0.0.1', 'port' => 6379]];
        }

        return $servers;
    }
}
