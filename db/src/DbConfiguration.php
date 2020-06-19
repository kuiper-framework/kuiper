<?php

declare(strict_types=1);

namespace kuiper\db;

use Aura\SqlQuery\QueryFactory;
use Carbon\Carbon;
use DI\Annotation\Inject;
use function DI\autowire;
use function DI\factory;
use kuiper\db\converter\AttributeConverterRegistry;
use kuiper\db\converter\BoolConverter;
use kuiper\db\converter\DateConverter;
use kuiper\db\converter\DateTimeConverter;
use kuiper\db\converter\PrimitiveConverter;
use kuiper\db\event\listener\LogStatementQuery;
use kuiper\db\metadata\MetaModelFactory;
use kuiper\db\metadata\MetaModelFactoryInterface;
use kuiper\db\metadata\NamingStrategy;
use kuiper\db\metadata\NamingStrategyInterface;
use kuiper\di\annotation\Bean;
use kuiper\di\annotation\ConditionalOnClass;
use kuiper\di\annotation\ConditionalOnMissingClass;
use kuiper\di\annotation\ConditionalOnProperty;
use kuiper\di\annotation\Configuration;
use kuiper\di\ContainerBuilderAwareTrait;
use kuiper\di\DefinitionConfiguration;
use kuiper\helper\Arrays;
use kuiper\logger\LoggerFactoryInterface;
use kuiper\reflection\ReflectionFileFactory;
use kuiper\reflection\ReflectionFileFactoryInterface;
use kuiper\reflection\ReflectionType;
use kuiper\swoole\pool\PoolConfig;
use Swoole\Coroutine\Channel;

/**
 * @Configuration()
 * @ConditionalOnProperty("application.database")
 */
class DbConfiguration implements DefinitionConfiguration
{
    use ContainerBuilderAwareTrait;

    public function getDefinitions(): array
    {
        return [
            ReflectionFileFactoryInterface::class => factory([ReflectionFileFactory::class, 'getInstance']),
            QueryBuilderInterface::class => autowire(QueryBuilder::class),
            MetaModelFactoryInterface::class => autowire(MetaModelFactory::class),
        ];
    }

    /**
     * @Bean()
     * @Inject({"tablePrefix" = "application.database.table-prefix"})
     */
    public function namingStrategy(?string $tablePrefix): NamingStrategyInterface
    {
        return new NamingStrategy($tablePrefix ?? '');
    }

    /**
     * @Bean()
     * @Inject({"config" = "application.database"})
     */
    public function connection(array $config): ConnectionInterface
    {
        return new Connection(
            $this->buildDsn($config),
            $config['user'] ?? 'root',
            $config['password'] ?? ''
        );
    }

    /**
     * @Bean()
     * @ConditionalOnMissingClass(Channel::class)
     */
    public function connectionPool(ConnectionInterface $connection): ConnectionPoolInterface
    {
        return new ConnectionPool($connection);
    }

    /**
     * @Bean()
     * @Inject({"config" = "application.database"})
     * @ConditionalOnClass(Channel::class)
     */
    public function swooleConnectionPool(array $config): ConnectionPoolInterface
    {
        $poolConfig = new PoolConfig();
        Arrays::assign($poolConfig, $config);

        return new SwooleConnectionPool($poolConfig,
            $this->buildDsn($config),
            $config['user'] ?: 'root',
            $config['password'] ?: ''
        );
    }

    /**
     * @Bean()
     */
    public function attributeConverterRegistry(DateTimeFactoryInterface $dateTimeFactory): AttributeConverterRegistry
    {
        $registry = new AttributeConverterRegistry();
        $registry->register('bool', new BoolConverter());
        foreach (['int', 'string', 'float'] as $typeName) {
            $type = ReflectionType::parse($typeName);
            $registry->register($type->getName(), new PrimitiveConverter($type));
        }
        $registry->register(\DateTime::class, new DateTimeConverter($dateTimeFactory));
        $registry->register(DateConverter::class, new DateConverter($dateTimeFactory));

        return $registry;
    }

    /**
     * @ConditionalOnMissingClass(Carbon::class)
     * @Bean()
     */
    public function dateTimeFactory(): DateTimeFactoryInterface
    {
        return new DateTimeFactory();
    }

    /**
     * @ConditionalOnClass(Carbon::class)
     * @Bean()
     */
    public function carbonDateTimeFactory(): DateTimeFactoryInterface
    {
        return new CarbonDateTimeFactory();
    }

    /**
     * @Bean()
     */
    public function queryFactory(): QueryFactory
    {
        return new QueryFactory('mysql');
    }

    /**
     * @Bean()
     * @ConditionalOnProperty("application.database.logging", hasValue=true)
     */
    public function logQueryEventListener(LoggerFactoryInterface $loggerFactory): LogStatementQuery
    {
        $listener = new LogStatementQuery();
        $listener->setLogger($loggerFactory->create(LogStatementQuery::class));

        return $listener;
    }

    protected function buildDsn(array $config): string
    {
        return sprintf('mysql:dbname=%s;host=%s;port=%d;charset=%s',
            $config['name'] ?? 'test',
            $config['host'] ?? 'localhost',
            $config['port'] ?? 3306,
            $config['charset'] ?? 'utf8mb4');
    }
}
