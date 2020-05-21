<?php

declare(strict_types=1);

namespace kuiper\db\orm;

use kuiper\db\Criteria;
use kuiper\db\orm\serializer\SerializerRegistry;
use kuiper\db\sharding\ClusterInterface;
use kuiper\db\sharding\Statement;
use kuiper\db\StatementInterface;

/**
 * Class ShardingRepository.
 *
 * @property ClusterInterface $connection
 */
class ShardingRepository extends AbstractRepository
{
    public function __construct(ClusterInterface $connection, TableMetadata $tableMetadata, SerializerRegistry $serializers)
    {
        parent::__construct($connection, $tableMetadata, $serializers);
    }

    public function insert($model)
    {
        $stmt = $this->buildInsertStatement($model);

        return $this->doExecute($stmt);
    }

    protected function doExecute(StatementInterface $stmt)
    {
        $this->checkShardFields($stmt);

        return parent::doExecute($stmt);
    }

    protected function doQuery(StatementInterface $stmt)
    {
        $this->checkShardFields($stmt);

        return parent::doQuery($stmt);
    }

    protected function buildStatementByCriteria(StatementInterface $stmt, Criteria $criteria)
    {
        parent::buildStatementByCriteria($stmt, $criteria);
        /* @var Statement $stmt */
        $stmt->shardBy($criteria->getBindValues());
    }

    /**
     * @param Statement $stmt
     */
    protected function checkShardFields($stmt)
    {
        $fields = $stmt->getShardBy();
        $missing = array_diff($this->tableMetadata->getShardBy(), array_keys($fields));
        if (!empty($missing)) {
            throw new \InvalidArgumentException(sprintf('Shard fields %s are required for table %s', json_encode($missing), $this->tableMetadata->getName()));
        }
    }
}
