<?php

declare(strict_types=1);

namespace kuiper\cache;

class RedisFactory
{
    private static $defaultConnectionOptions = [
        'class' => null,
        'persistent' => 0,
        'persistent_id' => null,
        'timeout' => 30,
        'read_timeout' => 0,
        'retry_interval' => 0,
        'tcp_keepalive' => 0,
        'lazy' => null,
        'redis_cluster' => false,
        'redis_sentinel' => null,
        'dbindex' => 0,
        'failover' => 'none',
    ];

    /**
     * Creates a Redis connection using a DSN configuration.
     *
     * Example DSN:
     *   - redis://localhost
     *   - redis://example.com:1234
     *   - redis://secret@example.com/13
     *   - redis:///var/run/redis.sock
     *   - redis://secret@/var/run/redis.sock/13
     *
     * @param array $options See self::$defaultConnectionOptions
     *
     * @return \Redis|\RedisCluster According to the "class" option
     *
     * @throws \InvalidArgumentException when the DSN is invalid
     */
    public static function createConnection(string $dsn, array $options = [])
    {
        if (0 === strpos($dsn, 'redis:')) {
            $scheme = 'redis';
        } elseif (0 === strpos($dsn, 'rediss:')) {
            $scheme = 'rediss';
        } else {
            throw new \InvalidArgumentException(sprintf('Invalid Redis DSN: "%s" does not start with "redis:" or "rediss".', $dsn));
        }

        if (!\extension_loaded('redis') && !class_exists(\Predis\Client::class)) {
            throw new \RuntimeException(sprintf('Cannot find the "redis" extension nor the "predis/predis" package: "%s".', $dsn));
        }

        $params = preg_replace_callback('#^'.$scheme.':(//)?(?:(?:[^:@]*+:)?([^@]*+)@)?#', function ($m) use (&$auth) {
            if (isset($m[2])) {
                $auth = $m[2];

                if ('' === $auth) {
                    $auth = null;
                }
            }

            return 'file:'.($m[1] ?? '');
        }, $dsn);

        if (false === $params = parse_url($params)) {
            throw new \InvalidArgumentException(sprintf('Invalid Redis DSN: "%s".', $dsn));
        }

        $query = $hosts = [];

        $tls = 'rediss' === $scheme;
        $tcpScheme = $tls ? 'tls' : 'tcp';

        if (isset($params['query'])) {
            parse_str($params['query'], $query);

            if (isset($query['host'])) {
                if (!\is_array($hosts = $query['host'])) {
                    throw new \InvalidArgumentException(sprintf('Invalid Redis DSN: "%s".', $dsn));
                }
                foreach ($hosts as $host => $parameters) {
                    if (\is_string($parameters)) {
                        parse_str($parameters, $parameters);
                    }
                    if (false === $i = strrpos($host, ':')) {
                        $hosts[$host] = ['scheme' => $tcpScheme, 'host' => $host, 'port' => 6379] + $parameters;
                    } elseif ($port = (int) substr($host, 1 + $i)) {
                        $hosts[$host] = ['scheme' => $tcpScheme, 'host' => substr($host, 0, $i), 'port' => $port] + $parameters;
                    } else {
                        $hosts[$host] = ['scheme' => 'unix', 'path' => substr($host, 0, $i)] + $parameters;
                    }
                }
                $hosts = array_values($hosts);
            }
        }

        if (isset($params['host']) || isset($params['path'])) {
            if (!isset($params['dbindex']) && isset($params['path']) && preg_match('#/(\d+)$#', $params['path'], $m)) {
                $params['dbindex'] = $m[1];
                $params['path'] = substr($params['path'], 0, -\strlen($m[0]));
            }

            if (isset($params['host'])) {
                array_unshift($hosts, ['scheme' => $tcpScheme, 'host' => $params['host'], 'port' => $params['port'] ?? 6379]);
            } else {
                array_unshift($hosts, ['scheme' => 'unix', 'path' => $params['path']]);
            }
        }

        if (!$hosts) {
            throw new \InvalidArgumentException(sprintf('Invalid Redis DSN: "%s".', $dsn));
        }

        $params += $query + $options + self::$defaultConnectionOptions;

        if (isset($params['redis_sentinel']) && !class_exists(\Predis\Client::class)) {
            throw new \RuntimeException(sprintf('Redis Sentinel support requires the "predis/predis" package: "%s".', $dsn));
        }

        if (null === $params['class'] && !isset($params['redis_sentinel']) && \extension_loaded('redis')) {
            $class = $params['redis_cluster'] ? \RedisCluster::class : (1 < \count($hosts) ? \RedisArray::class : \Redis::class);
        } else {
            $class = null === $params['class'] ? \Predis\Client::class : $params['class'];
        }

        if (is_a($class, \Redis::class, true)) {
            $connect = $params['persistent'] || $params['persistent_id'] ? 'pconnect' : 'connect';
            $redis = new $class();

            $host = $hosts[0]['host'] ?? $hosts[0]['path'];
            $port = $hosts[0]['port'] ?? null;

            if (isset($hosts[0]['host']) && $tls) {
                $host = 'tls://'.$host;
            }

            try {
                @$redis->{$connect}($host, $port, $params['timeout'], (string) $params['persistent_id'], $params['retry_interval'], $params['read_timeout']);

                set_error_handler(function ($type, $msg) use (&$error) {
                    $error = $msg;
                });
                $isConnected = $redis->isConnected();
                restore_error_handler();
                if (!$isConnected) {
                    $error = preg_match('/^Redis::p?connect\(\): (.*)/', $error, $error) ? sprintf(' (%s)', $error[1]) : '';
                    throw new \InvalidArgumentException(sprintf('Redis connection "%s" failed: ', $dsn).$error.'.');
                }

                if ((null !== $auth && !$redis->auth($auth))
                    || ($params['dbindex'] && !$redis->select((int) $params['dbindex']))
                ) {
                    $e = preg_replace('/^ERR /', '', $redis->getLastError());
                    throw new \InvalidArgumentException(sprintf('Redis connection "%s" failed: ', $dsn).$e.'.');
                }

                if (0 < $params['tcp_keepalive'] && \defined('Redis::OPT_TCP_KEEPALIVE')) {
                    $redis->setOption(\Redis::OPT_TCP_KEEPALIVE, $params['tcp_keepalive']);
                }
            } catch (\RedisException $e) {
                throw new \InvalidArgumentException(sprintf('Redis connection "%s" failed: ', $dsn).$e->getMessage());
            }
        } elseif (is_a($class, \RedisArray::class, true)) {
            foreach ($hosts as $i => $host) {
                switch ($host['scheme']) {
                    case 'tcp':
                        $hosts[$i] = $host['host'].':'.$host['port'];
                        break;
                    case 'tls':
                        $hosts[$i] = 'tls://'.$host['host'].':'.$host['port'];
                        break;
                    default:
                        $hosts[$i] = $host['path'];
                }
            }
            $params['lazy_connect'] = $params['lazy'] ?? true;
            $params['connect_timeout'] = $params['timeout'];

            try {
                $redis = new $class($hosts, $params);
            } catch (\RedisClusterException $e) {
                throw new \InvalidArgumentException(sprintf('Redis connection "%s" failed: ', $dsn).$e->getMessage());
            }

            if (0 < $params['tcp_keepalive'] && \defined('Redis::OPT_TCP_KEEPALIVE')) {
                $redis->setOption(\Redis::OPT_TCP_KEEPALIVE, $params['tcp_keepalive']);
            }
        } elseif (is_a($class, \RedisCluster::class, true)) {
            foreach ($hosts as $i => $host) {
                switch ($host['scheme']) {
                    case 'tcp':
                        $hosts[$i] = $host['host'].':'.$host['port'];
                        break;
                    case 'tls':
                        $hosts[$i] = 'tls://'.$host['host'].':'.$host['port'];
                        break;
                    default:
                        $hosts[$i] = $host['path'];
                }
            }

            try {
                $redis = new $class(null, $hosts, $params['timeout'], $params['read_timeout'], (bool) $params['persistent'], $params['auth'] ?? '');
            } catch (\RedisClusterException $e) {
                throw new \InvalidArgumentException(sprintf('Redis connection "%s" failed: ', $dsn).$e->getMessage());
            }

            if (0 < $params['tcp_keepalive'] && \defined('Redis::OPT_TCP_KEEPALIVE')) {
                $redis->setOption(\Redis::OPT_TCP_KEEPALIVE, $params['tcp_keepalive']);
            }
            switch ($params['failover']) {
                case 'error':
                    $redis->setOption(\RedisCluster::OPT_SLAVE_FAILOVER, \RedisCluster::FAILOVER_ERROR);
                    break;
                case 'distribute':
                    $redis->setOption(\RedisCluster::OPT_SLAVE_FAILOVER, \RedisCluster::FAILOVER_DISTRIBUTE);
                    break;
                case 'slaves':
                    $redis->setOption(\RedisCluster::OPT_SLAVE_FAILOVER, \RedisCluster::FAILOVER_DISTRIBUTE_SLAVES);
                    break;
            }
        } elseif (is_a($class, \Predis\ClientInterface::class, true)) {
            if ($params['redis_cluster']) {
                $params['cluster'] = 'redis';
                if (isset($params['redis_sentinel'])) {
                    throw new \InvalidArgumentException(sprintf('Cannot use both "redis_cluster" and "redis_sentinel" at the same time: "%s".', $dsn));
                }
            } elseif (isset($params['redis_sentinel'])) {
                $params['replication'] = 'sentinel';
                $params['service'] = $params['redis_sentinel'];
            }
            $params += ['parameters' => []];
            $params['parameters'] += [
                'persistent' => $params['persistent'],
                'timeout' => $params['timeout'],
                'read_write_timeout' => $params['read_timeout'],
                'tcp_nodelay' => true,
            ];
            if ($params['dbindex']) {
                $params['parameters']['database'] = $params['dbindex'];
            }
            if (null !== $auth) {
                $params['parameters']['password'] = $auth;
            }
            if (1 === \count($hosts) && !($params['redis_cluster'] || $params['redis_sentinel'])) {
                $hosts = $hosts[0];
            } elseif (\in_array($params['failover'], ['slaves', 'distribute'], true) && !isset($params['replication'])) {
                $params['replication'] = true;
                $hosts[0] += ['alias' => 'master'];
            }
            $params['exceptions'] = false;

            $redis = new $class($hosts, array_diff_key($params, self::$defaultConnectionOptions));
            if (isset($params['redis_sentinel'])) {
                $redis->getConnection()->setSentinelTimeout($params['timeout']);
            }
        } elseif (class_exists($class, false)) {
            throw new \InvalidArgumentException(sprintf('"%s" is not a subclass of "Redis", "RedisArray", "RedisCluster" nor "Predis\ClientInterface".', $class));
        } else {
            throw new \InvalidArgumentException(sprintf('Class "%s" does not exist.', $class));
        }

        return $redis;
    }
}
