<?php

declare(strict_types=1);

namespace kuiper\db\sharding;

use kuiper\db\AbstractCrudRepository;
use kuiper\db\Criteria;
use kuiper\db\DateTimeFactoryInterface;
use kuiper\db\metadata\MetaModelFactoryInterface;
use kuiper\db\StatementInterface;
use PDOStatement;
use Psr\EventDispatcher\EventDispatcherInterface;

abstract class AbstractShardingCrudRepository extends AbstractCrudRepository
{
    public function __construct(ClusterInterface $cluster,
                                MetaModelFactoryInterface $metaModelFactory,
                                DateTimeFactoryInterface $dateTimeFactory,
                                EventDispatcherInterface $eventDispatcher)
    {
        parent::__construct($cluster, $metaModelFactory, $dateTimeFactory, $eventDispatcher);
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

    protected function buildStatement(StatementInterface $stmt, $criteria): StatementInterface
    {
        if ($criteria instanceof Criteria && $stmt instanceof Statement) {
            $stmt->shardBy($criteria->getBindValues());
        }

        return parent::buildStatement($stmt, $criteria);
    }

    protected function checkShardFields(StatementInterface $stmt): void
    {
        $fields = $stmt->getShardBy();
        $missing = array_diff($this->metaModel->getShardBy(), array_keys($fields));
        if (!empty($missing)) {
            throw new \InvalidArgumentException(sprintf('Shard fields %s are required for table %s', json_encode($missing), $this->tableMetadata->getTableName()));
        }
    }
}
