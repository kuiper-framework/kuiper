<?php

declare(strict_types=1);

namespace kuiper\db\sharding;

use kuiper\db\AbstractCrudRepository;
use kuiper\db\annotation\ShardKey;
use kuiper\db\Criteria;
use kuiper\db\DateTimeFactoryInterface;
use kuiper\db\exception\MetaModelException;
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
        if (empty($this->shardKeys)) {
            throw new MetaModelException($this->metaModel->getEntityClass()->getName().' does not contain any sharding columns, please annotate property with @'.ShardKey::class);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function batchInsert(array $entities): array
    {
        if (empty($entities)) {
            return [];
        }
        $result = [];
        foreach (Arrays::groupBy($entities, function ($entity): string {
            return $this->getShardingId($entity);
        }) as $partEntities) {
            $result[] = parent::batchInsert($partEntities);
        }

        return Arrays::flatten($result);
    }

    /**
     * {@inheritdoc}
     */
    public function batchUpdate(array $entities): array
    {
        if (empty($entities)) {
            return [];
        }
        $result = [];
        foreach (Arrays::groupBy($entities, function ($entity): string {
            return $this->getShardingId($entity);
        }) as $partEntities) {
            if (1 === count($partEntities)) {
                $this->update($partEntities[0]);
            } else {
                $stmt = $this->buildBatchUpdateStatement($partEntities);
                /* @var \kuiper\db\sharding\StatementInterface $stmt */
                $stmt->shardBy($this->getShardFields($partEntities[0]));
                $this->doExecute($stmt);
            }
            $result[] = $partEntities;
        }

        return Arrays::flatten($result);
    }

    /**
     * {@inheritdoc}
     */
    public function findAllByNaturalId(array $examples): array
    {
        if (empty($examples)) {
            return [];
        }
        $result = [];
        foreach (Arrays::groupBy($examples, function ($entity): string {
            return $this->getShardingId($entity);
        }) as $partEntities) {
            if (1 === count($partEntities)) {
                $exist = $this->findByNaturalId($partEntities[0]);
                if ($exist) {
                    $result[] = [$exist];
                }
            } else {
                $values = [];
                foreach ($partEntities as $example) {
                    $criteria = $values[] = $this->metaModel->getNaturalIdValues($example);
                    if (!isset($criteria)) {
                        throw new \InvalidArgumentException('Cannot extract unique constraint from input');
                    }
                }
                $shardFields = $this->getShardFields($partEntities[0]);
                // 不能直接使用 Criteria 对象，因为  criteria 对象会被 filterCriteria 进行值转换
                $result[] = $this->findAllBy(static function ($stmt) use ($values, $shardFields): StatementInterface {
                    $stmt->shardBy($shardFields);

                    return Criteria::create()
                        ->matches($values, array_keys($values[0]))
                        ->buildStatement($stmt);
                });
            }
        }

        return Arrays::flatten($result);
    }

    /**
     * @param object $entity
     */
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
            throw new \InvalidArgumentException(sprintf('Shard fields %s are required for table %s', json_encode($missing), $this->getTableName()));
        }
    }

    /**
     * @param object $entity
     */
    protected function getShardFields($entity): array
    {
        $this->checkEntityClassMatch($entity);
        $shard = [];
        foreach ($this->shardKeys as $field) {
            $value = $this->metaModel->getValue($entity, $field);
            if (!isset($value)) {
                throw new \InvalidArgumentException("sharding column $field cannot be null");
            }
            $shard[$field] = $value;
        }

        return $shard;
    }
}
