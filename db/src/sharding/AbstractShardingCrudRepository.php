<?php

declare(strict_types=1);

namespace kuiper\db\sharding;

use kuiper\db\AbstractCrudRepository;
use kuiper\db\annotation\ShardKey;
use kuiper\db\Criteria;
use kuiper\db\DateTimeFactoryInterface;
use kuiper\db\metadata\MetaModelFactoryInterface;
use kuiper\db\StatementInterface;
use PDOStatement;
use Psr\EventDispatcher\EventDispatcherInterface;

abstract class AbstractShardingCrudRepository extends AbstractCrudRepository
{
    /**
     * @var array
     */
    private $shardKeys;

    public function __construct(ClusterInterface $cluster,
                                MetaModelFactoryInterface $metaModelFactory,
                                DateTimeFactoryInterface $dateTimeFactory,
                                EventDispatcherInterface $eventDispatcher)
    {
        parent::__construct($cluster, $metaModelFactory, $dateTimeFactory, $eventDispatcher);
        foreach ($this->metaModel->getColumns() as $column) {
            if ($column->getProperty()->hasAnnotation(ShardKey::class)) {
                $this->shardKeys[] = $column->getName();
            }
        }
    }

    protected function doExecute(StatementInterface $stmt): void
    {
        $this->checkShardFields($stmt);

        parent::doExecute($stmt);
    }

    protected function doQuery(StatementInterface $stmt): PDOStatement
    {
        $this->checkShardFields($stmt);

        return parent::doQuery($stmt);
    }

    protected function buildStatementByCriteria(StatementInterface $stmt, Criteria $criteria): StatementInterface
    {
        $stmt->shardBy($criteria->getBindValues());

        return parent::buildStatementByCriteria($stmt, $criteria);
    }

    protected function checkShardFields(StatementInterface $stmt): void
    {
        $fields = $stmt->getShardBy();

        $missing = array_diff($this->shardKeys, array_keys($fields));
        if (!empty($missing)) {
            throw new \InvalidArgumentException(sprintf('Shard fields %s are required for table %s', json_encode($missing), $this->tableMetadata->getTableName()));
        }
    }
}
