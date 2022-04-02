<?php

declare(strict_types=1);

namespace kuiper\db;

use kuiper\db\fixtures\Item;
use kuiper\db\fixtures\ItemRepository;
use kuiper\db\metadata\MetaModelFactory;
use kuiper\db\metadata\NamingStrategy;
use function kuiper\helper\env;
use kuiper\swoole\pool\PoolConfig;
use kuiper\swoole\pool\SimplePool;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\NullLogger;
use Symfony\Component\EventDispatcher\EventDispatcher;

class PooledTransactionManagerTest extends AbstractRepositoryTestCase
{
    public function createConnection(EventDispatcherInterface $eventDispatcher): Connection
    {
        $config = $this->getConfig();
        $conn = new Connection($config[0], $config[1], $config[2]);
        $conn->setEventDispatcher($eventDispatcher);

        return $conn;
    }

    public function getConfig()
    {
        $config = [
            sprintf('mysql:dbname=%s;host=%s;port=%d;charset=%s',
                env('DB_NAME', 'test'),
                env('DB_HOST', 'localhost'),
                env('DB_PORT') ?: 3306,
                env('DB_CHARSET', 'utf8mb4')),
            env('DB_USER', 'root'),
            env('DB_PASS', ''),
        ];

        return $config;
    }

    public function testTransaction()
    {
        $eventDispatcher = new EventDispatcher();
        $pool = new SimplePool('db', function () use ($eventDispatcher) {
            error_log('create connection');

            return $this->createConnection($eventDispatcher);
        }, new PoolConfig(), $eventDispatcher, new NullLogger());
        $connectionPool = new ConnectionPool($pool);
        $tm = new PooledTransactionManager($connectionPool);
        $repository = new ItemRepository(
            new QueryBuilder($connectionPool, null, $eventDispatcher),
            new MetaModelFactory($this->createAttributeRegistry(), new NamingStrategy('test_'), null, null),
            new DateTimeFactory(),
            $eventDispatcher);
        $tm->transaction(function () use ($repository) {
            $item = new Item();
            $item->setItemId(1);
            $item->setItemNo('01');
            $item->setSharding(1);
            $repository->delete($item);
            $repository->insert($item);
        });
    }
}
