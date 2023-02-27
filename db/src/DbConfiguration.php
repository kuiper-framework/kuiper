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

namespace kuiper\db;

use Aura\SqlQuery\QueryFactory;
use Carbon\Carbon;
use DI\Attribute\Inject;
use kuiper\db\converter\AttributeConverterRegistry;
use kuiper\db\converter\DateConverter;
use kuiper\db\converter\DateTimeConverter;
use kuiper\db\event\listener\LogStatementQuery;
use kuiper\db\metadata\MetaModelFactory;
use kuiper\db\metadata\MetaModelFactoryInterface;
use kuiper\db\metadata\NamingStrategy;
use kuiper\db\metadata\NamingStrategyInterface;
use kuiper\di\attribute\Bean;
use kuiper\di\attribute\ConditionalOnClass;
use kuiper\di\attribute\ConditionalOnMissingClass;
use kuiper\di\attribute\ConditionalOnProperty;
use kuiper\di\attribute\Configuration;
use kuiper\di\ContainerBuilderAwareTrait;
use kuiper\di\DefinitionConfiguration;
use kuiper\logger\LoggerFactoryInterface;
use kuiper\reflection\ReflectionFileFactory;
use kuiper\reflection\ReflectionFileFactoryInterface;
use kuiper\swoole\pool\PoolFactoryInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use Swoole\Coroutine\Channel;
use function DI\autowire;
use function DI\factory;

#[Configuration]
#[ConditionalOnProperty('application.database')]
class DbConfiguration implements DefinitionConfiguration
{
    use ContainerBuilderAwareTrait;

    public function getDefinitions(): array
    {
        return [
            ReflectionFileFactoryInterface::class => factory([ReflectionFileFactory::class, 'getInstance']),
            QueryBuilderInterface::class => autowire(QueryBuilder::class),
            MetaModelFactoryInterface::class => autowire(MetaModelFactory::class),
            TransactionManagerInterface::class => autowire(PooledTransactionManager::class),
        ];
    }

    #[Bean]
    public function namingStrategy(#[Inject('application.database.table_prefix')] ?string $tablePrefix): NamingStrategyInterface
    {
        return new NamingStrategy($tablePrefix ?? '');
    }

    #[Bean]
    public function connection(EventDispatcherInterface $eventDispatcher,
                               #[Inject('application.database')] array $config): ConnectionInterface
    {
        $connection = new Connection(
            $this->buildDsn($config),
            $config['user'] ?? 'root',
            $config['password'] ?? ''
        );
        $connection->setEventDispatcher($eventDispatcher);

        return $connection;
    }

    #[Bean]
    #[ConditionalOnMissingClass(Channel::class)]
    public function connectionPool(ConnectionInterface $connection): ConnectionPoolInterface
    {
        return new SingleConnectionPool($connection);
    }

    #[Bean]
    #[ConditionalOnClass(Channel::class)]
    public function swooleConnectionPool(
        PoolFactoryInterface $poolFactory,
        EventDispatcherInterface $eventDispatcher,
        #[Inject('application.database')] array $config): ConnectionPoolInterface
    {
        return new ConnectionPool($poolFactory->create('db', function () use ($eventDispatcher, $config): ConnectionInterface {
            return $this->connection($eventDispatcher, $config);
        }));
    }

    #[Bean]
    public function attributeConverterRegistry(DateTimeFactoryInterface $dateTimeFactory): AttributeConverterRegistry
    {
        $registry = AttributeConverterRegistry::createDefault();
        $registry->register(\DateTimeInterface::class, new DateTimeConverter($dateTimeFactory));
        $registry->register(DateConverter::class, new DateConverter($dateTimeFactory));

        return $registry;
    }

    #[Bean]
    #[ConditionalOnMissingClass(Carbon::class)]
    public function dateTimeFactory(): DateTimeFactoryInterface
    {
        return new DateTimeFactory();
    }

    #[Bean]
    #[ConditionalOnClass(Carbon::class)]
    public function carbonDateTimeFactory(): DateTimeFactoryInterface
    {
        return new CarbonDateTimeFactory();
    }

    #[Bean]
    public function queryFactory(#[Inject('application.database.driver')] ?string $driver): QueryFactory
    {
        return new QueryFactory($driver ?? 'mysql');
    }

    #[Bean]
    #[ConditionalOnProperty('application.database.logging', hasValue: true)]
    public function logQueryEventListener(LoggerFactoryInterface $loggerFactory): LogStatementQuery
    {
        $listener = new LogStatementQuery();
        $listener->setLogger($loggerFactory->create(LogStatementQuery::class));

        return $listener;
    }

    protected function buildDsn(array $config): string
    {
        $dsn = sprintf('%s:dbname=%s;host=%s;port=%d;',
            $config['driver'] ?? 'mysql',
            $config['name'] ?? 'test',
            $config['host'] ?? 'localhost',
            $config['port'] ?? 3306);
        if (isset($config['charset'])) {
            $dsn .= 'charset='.$config['charset'];
        }

        return $dsn;
    }
}
