<?php

declare(strict_types=1);

namespace kuiper\db\sharding;

use kuiper\db\QueryBuilderInterface;
use kuiper\db\RepositoryInterface;
use kuiper\db\RepositoryTrait;
use kuiper\helper\Arrays;
use Webmozart\Assert\Assert;

class Repository implements RepositoryInterface
{
    use RepositoryTrait;

    /**
     * @var string[]
     */
    private $shardFields;

    public function __construct(QueryBuilderInterface $queryBuilder, $modelClass, $table, array $shardFields, $timestampFields = ['create_time', 'update_time'], $datetimeFormat = 'Y-m-d H:i:s')
    {
        $this->queryBuilder = $queryBuilder;
        $this->modelClass = $modelClass;
        $this->table = $table;
        $this->shardFields = $shardFields;
        $this->timestampFields = $timestampFields;
        $this->datetimeFormat = $datetimeFormat;
    }

    /**
     * {@inheritdoc}
     */
    public function insert($model)
    {
        $stmt = $this->buildInsertStatement($model);
        $this->checkShardFields($stmt);

        return $stmt->execute();
    }

    /**
     * {@inheritdoc}
     */
    public function update($model, $condition = null)
    {
        $stmt = $this->buildUpdateStatement($model, $condition);
        $this->checkShardFields($stmt);

        return $stmt->execute() && $stmt->rowCount() > 0;
    }

    /**
     * {@inheritdoc}
     */
    public function delete($condition)
    {
        $stmt = $this->buildStatement($this->queryBuilder->delete($this->table), $condition, false);
        $this->checkShardFields($stmt);

        return $stmt->execute() && $stmt->rowCount() > 0;
    }

    /**
     * {@inheritdoc}
     */
    public function count($condition = null)
    {
        Assert::notEmpty($condition);
        $stmt = $this->buildCountStatement($condition);
        $this->checkShardFields($stmt);
        $count = $stmt->query()
               ->fetchColumn(0);
        $stmt->close();

        return $count;
    }

    /**
     * {@inheritdoc}
     */
    public function findOne($condition)
    {
        $stmt = $this->queryBuilder->from($this->table)
              ->select($this->getColumns());
        $stmt = $this->buildStatement($stmt, $condition);
        $this->checkShardFields($stmt);
        $row = $stmt->query()->fetch();
        $stmt->close();

        return $row ? Arrays::assign(new $this->modelClass(), $row) : false;
    }

    /**
     * {@inheritdoc}
     */
    public function query($condition, $returnModel = true)
    {
        $stmt = $this->queryBuilder->from($this->table)
              ->select($this->getColumns());
        $this->buildStatement($stmt, $condition);
        $this->checkShardFields($stmt);
        $rows = $stmt->query()
            ->fetchAll();
        if ($returnModel) {
            return array_map(function ($row) {
                return Arrays::assign(new $this->modelClass(), $row);
            }, $rows);
        } else {
            return $rows;
        }
    }

    protected function checkShardFields($stmt)
    {
        $fields = $stmt->getShardBy();
        $missing = array_diff($this->shardFields, array_keys($fields));
        if (!empty($missing)) {
            throw new \InvalidArgumentException(sprintf('Sharding field %s are required for table %s', json_encode($missing), $this->table));
        }
    }
}
