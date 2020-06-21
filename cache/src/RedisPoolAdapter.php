<?php

declare(strict_types=1);

namespace kuiper\cache;

use kuiper\swoole\pool\PoolInterface;
use Predis\ClientInterface;
use Predis\Connection\Aggregate\ClusterInterface;
use Predis\Response\Status;
use Symfony\Component\Cache\Adapter\AbstractAdapter;
use Symfony\Component\Cache\Marshaller\DefaultMarshaller;
use Symfony\Component\Cache\Marshaller\MarshallerInterface;
use Symfony\Component\Cache\Traits\RedisClusterProxy;

class RedisPoolAdapter extends AbstractAdapter
{
    /**
     * @var PoolInterface
     */
    private $redisPool;

    /**
     * @var MarshallerInterface
     */
    private $marshaller;

    /**
     * @param PoolInterface $redisPool       The redis connection pool
     * @param string        $namespace       The default namespace
     * @param int           $defaultLifetime The default lifetime
     */
    public function __construct($redisPool, string $namespace = '', int $defaultLifetime = 0, MarshallerInterface $marshaller = null)
    {
        parent::__construct($namespace, $defaultLifetime);

        if (preg_match('#[^-+_.A-Za-z0-9]#', $namespace, $match)) {
            throw new \InvalidArgumentException(sprintf('RedisAdapter namespace contains "%s" but only characters in [-+_.A-Za-z0-9] are allowed.', $match[0]));
        }
        $this->redisPool = $redisPool;
        $this->marshaller = $marshaller ?? new DefaultMarshaller();
    }

    /**
     * {@inheritdoc}
     */
    protected function doFetch(array $ids)
    {
        if (!$ids) {
            return [];
        }
        $redis = $this->redisPool->take();

        $result = [];

        if ($redis instanceof ClientInterface && $redis->getConnection() instanceof ClusterInterface) {
            $values = $this->pipeline(static function () use ($ids) {
                foreach ($ids as $id) {
                    yield 'get' => [$id];
                }
            }, $redis);
        } else {
            $values = $redis->mget($ids);

            if (!\is_array($values) || \count($values) !== \count($ids)) {
                return [];
            }

            $values = array_combine($ids, $values);
        }

        foreach ($values as $id => $v) {
            if ($v) {
                $result[$id] = $this->marshaller->unmarshall($v);
            }
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    protected function doHave(string $id)
    {
        return (bool) $this->redisPool->take()->exists($id);
    }

    /**
     * {@inheritdoc}
     */
    protected function doClear(string $namespace)
    {
        $cleared = true;
        $redis = $this->redisPool->take();

        if ($redis instanceof ClientInterface) {
            $evalArgs = [0, $namespace];
        } else {
            $evalArgs = [[$namespace], 0];
        }

        foreach ($this->getHosts($redis) as $host) {
            if (!isset($namespace[0])) {
                $cleared = $host->flushDb() && $cleared;
                continue;
            }

            $info = $host->info('Server');
            $info = isset($info['Server']) ? $info['Server'] : $info;

            if (!version_compare($info['redis_version'], '2.8', '>=')) {
                // As documented in Redis documentation (http://redis.io/commands/keys) using KEYS
                // can hang your server when it is executed against large databases (millions of items).
                // Whenever you hit this scale, you should really consider upgrading to Redis 2.8 or above.
                $cleared = $host->eval("local keys=redis.call('KEYS',ARGV[1]..'*') for i=1,#keys,5000 do redis.call('DEL',unpack(keys,i,math.min(i+4999,#keys))) end return 1", $evalArgs[0], $evalArgs[1]) && $cleared;
                continue;
            }

            $cursor = null;
            do {
                $keys = $host instanceof ClientInterface
                    ? $host->scan($cursor, 'MATCH', $namespace.'*', 'COUNT', 1000)
                    : $host->scan($cursor, $namespace.'*', 1000);
                if (isset($keys[1]) && \is_array($keys[1])) {
                    $cursor = $keys[0];
                    $keys = $keys[1];
                }
                if ($keys) {
                    $this->doDelete($keys);
                }
            } while ($cursor = (int) $cursor);
        }

        return $cleared;
    }

    /**
     * {@inheritdoc}
     */
    protected function doDelete(array $ids)
    {
        if (!$ids) {
            return true;
        }
        $redis = $this->redisPool->take();

        if ($redis instanceof ClientInterface && $redis->getConnection() instanceof ClusterInterface) {
            $this->pipeline(static function () use ($ids) {
                foreach ($ids as $id) {
                    yield 'del' => [$id];
                }
            }, $redis)->rewind();
        } else {
            $redis->del($ids);
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    protected function doSave(array $values, int $lifetime)
    {
        if (!$values = $this->marshaller->marshall($values, $failed)) {
            return $failed;
        }
        $redis = $this->redisPool->take();

        $results = $this->pipeline(static function () use ($values, $lifetime) {
            foreach ($values as $id => $value) {
                if (0 >= $lifetime) {
                    yield 'set' => [$id, $value];
                } else {
                    yield 'setEx' => [$id, $lifetime, $value];
                }
            }
        }, $redis);

        foreach ($results as $id => $result) {
            if (true !== $result && (!$result instanceof Status || Status::get('OK') !== $result)) {
                $failed[] = $id;
            }
        }

        return $failed;
    }

    private function pipeline(\Closure $generator, $redis): \Generator
    {
        $ids = [];

        if ($redis instanceof RedisClusterProxy || $redis instanceof \RedisCluster || ($redis instanceof ClientInterface && $redis->getConnection() instanceof RedisCluster)) {
            // phpredis & predis don't support pipelining with RedisCluster
            // see https://github.com/phpredis/phpredis/blob/develop/cluster.markdown#pipelining
            // see https://github.com/nrk/predis/issues/267#issuecomment-123781423
            $results = [];
            foreach ($generator() as $command => $args) {
                $results[] = $redis->{$command}(...$args);
                $ids[] = 'eval' === $command ? ($redis instanceof ClientInterface ? $args[2] : $args[1][0]) : $args[0];
            }
        } elseif ($redis instanceof ClientInterface) {
            $results = $redis->pipeline(static function ($redis) use ($generator, &$ids) {
                foreach ($generator() as $command => $args) {
                    $redis->{$command}(...$args);
                    $ids[] = 'eval' === $command ? $args[2] : $args[0];
                }
            });
        } elseif ($redis instanceof \RedisArray) {
            $connections = $results = $ids = [];
            foreach ($generator() as $command => $args) {
                $id = 'eval' === $command ? $args[1][0] : $args[0];
                if (!isset($connections[$h = $redis->_target($id)])) {
                    $connections[$h] = [$redis->_instance($h), -1];
                    $connections[$h][0]->multi(\Redis::PIPELINE);
                }
                $connections[$h][0]->{$command}(...$args);
                $results[] = [$h, ++$connections[$h][1]];
                $ids[] = $id;
            }
            foreach ($connections as $h => $c) {
                $connections[$h] = $c[0]->exec();
            }
            foreach ($results as $k => list($h, $c)) {
                $results[$k] = $connections[$h][$c];
            }
        } else {
            $redis->multi(\Redis::PIPELINE);
            foreach ($generator() as $command => $args) {
                $redis->{$command}(...$args);
                $ids[] = 'eval' === $command ? $args[1][0] : $args[0];
            }
            $results = $redis->exec();
        }

        foreach ($ids as $k => $id) {
            yield $id => $results[$k];
        }
    }

    private function getHosts($redis): array
    {
        $hosts = [$redis];
        if ($redis instanceof ClientInterface) {
            $connection = $redis->getConnection();
            if ($connection instanceof ClusterInterface && $connection instanceof \Traversable) {
                $hosts = [];
                foreach ($connection as $c) {
                    $hosts[] = new \Predis\Client($c);
                }
            }
        } elseif ($redis instanceof \RedisArray) {
            $hosts = [];
            foreach ($redis->_hosts() as $host) {
                $hosts[] = $redis->_instance($host);
            }
        } elseif ($redis instanceof RedisClusterProxy || $redis instanceof \RedisCluster) {
            $hosts = [];
            foreach ($redis->_masters() as $host) {
                $hosts[] = $h = new \Redis();
                $h->connect($host[0], $host[1]);
            }
        }

        return $hosts;
    }
}
