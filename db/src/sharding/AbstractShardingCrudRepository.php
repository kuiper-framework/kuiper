<?php

declare(strict_types=1);

namespace kuiper\db\sharding;

use kuiper\db\AbstractCrudRepository;
use kuiper\db\annotation\ShardKey;
use kuiper\db\Criteria;
use kuiper\db\DateTimeFactoryInterface;
use kuiper\db\metadata\MetaModelFactoryInterface;
use kuiper\db\StatementInterface;
use kuiper\helper\Arrays;
use PDOStatement;
use Psr\EventDispatcher\EventDispatcherInterface;

abstract class AbstractShardingCrudRepository extends AbstractCrudRepository
{
    /**
     * @var array
     */
    protected $shardKeys;

    /**
     * @var ClusterInterface
     */
    protected $cluster;

    public function __construct(ClusterInterface $cluster,
                                MetaModelFactoryInterface $metaModelFactory,
                                DateTimeFactoryInterface $dateTimeFactory,
                                EventDispatcherInterface $eventDispatcher)
    {
        $this->cluster = $cluster;
        parent::__construct($cluster, $metaModelFactory, $dateTimeFactory, $eventDispatcher);
        foreach ($this->metaModel->getColumns() as $column) {
            if ($column->getProperty()->hasAnnotation(ShardKey::class)) {
                $this->shardKeys[] = $column->getName();
            }
        }
    }

    public function batchInsert(array $entities): array
    {
        if (empty($entities)) {
            return [];
        }
        $result = [];
        foreach (Arrays::groupBy($entities, function ($entity) {
            return $this->getShardingId($entity);
        }) as $partEntities) {
            $result[] = parent::batchInsert($partEntities);
        }

        return array_merge(...$result);
    }

    public function batchUpdate(array $entities): array
    {
        if (empty($entities)) {
            return [];
        }
        $result = [];
        foreach (Arrays::groupBy($entities, function ($entity) {
            return $this->getShardingId($entity);
        }) as $partEntities) {
            if (1 === count($partEntities)) {
                $result[] = [$this->update($partEntities[0])];
            } else {
                $stmt = $this->buildBatchUpdateStatement($partEntities);
                $stmt->shardBy($this->getShardFields($partEntities[0]));
                $this->doExecute($stmt);
            }
            $result[] = $partEntities;
        }

        return array_merge(...$result);
    }

    public function getShardingId($entity): string
    {
        $strategy = $this->cluster->getTableStrategy($this->getTableName());
        $shard = $this->getShardFields($entity);

        return $strategy->getDb($shard).':'.$strategy->getTable($shard, $this->getTableName());
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

    /**
     * @param $entity
     */
    protected function getShardFields($entity): array
    {
        $shard = [];
        foreach ($this->shardKeys as $field) {
            $shard[$field] = $this->metaModel->getValue($entity, $field);
        }

        return $shard;
    }
}
