<?php

declare(strict_types=1);

namespace kuiper\db\orm;

use Carbon\Carbon;
use kuiper\db\constants\SqlState;
use kuiper\db\Criteria;
use kuiper\db\orm\serializer\SerializerRegistry;
use kuiper\db\PdoInterface;
use kuiper\db\QueryBuilderInterface;
use kuiper\db\StatementInterface;
use kuiper\helper\Arrays;
use Webmozart\Assert\Assert;

abstract class AbstractRepository implements RepositoryInterface
{
    /**
     * @var QueryBuilderInterface
     */
    protected $connection;
    /**
     * @var TableMetadata
     */
    protected $tableMetadata;
    /**
     * @var ModelTransformer
     */
    protected $modelTransformer;

    /**
     * @var StatementInterface
     */
    protected $lastStatement;

    /**
     * Repository constructor.
     */
    public function __construct(QueryBuilderInterface $connection, TableMetadata $tableMetadata, SerializerRegistry $serializers)
    {
        $this->connection = $connection;
        $this->tableMetadata = $tableMetadata;
        $this->modelTransformer = new ModelTransformer($tableMetadata, $serializers);
    }

    public function getConnection(): QueryBuilderInterface
    {
        return $this->connection;
    }

    /**
     * @return QueryBuilderInterface
     */
    public function getQueryBuilder()
    {
        return $this->connection;
    }

    public function getTableMetadata(): TableMetadata
    {
        return $this->tableMetadata;
    }

    public function getModelTransformer(): ModelTransformer
    {
        return $this->modelTransformer;
    }

    public function getLastStatement()
    {
        return $this->lastStatement;
    }

    /**
     * {@inheritdoc}
     */
    public function insert($model)
    {
        $stmt = $this->buildInsertStatement($model);
        if ($this->doExecute($stmt)) {
            if ($this->tableMetadata->getAutoIncrementColumn() && $this->connection instanceof PdoInterface) {
                $value = $this->modelTransformer->get($model, $this->tableMetadata->getAutoIncrementColumn());
                if (!$value) {
                    $this->modelTransformer->set($model, $this->tableMetadata->getAutoIncrementColumn(), $this->connection->lastInsertId());
                }
            }

            return true;
        } else {
            return false;
        }
    }

    /**
     * @param object         $model
     * @param array|callable $condition
     *
     * @return bool
     */
    public function update($model, $condition = null)
    {
        $stmt = $this->buildUpdateStatement($model, $condition);

        return $this->doExecute($stmt);
    }

    public function save($model)
    {
        $primaryKeys = $this->getConstraints($model);
        $stmt = $this->connection->from($this->tableMetadata->getName())
            ->select(array_keys($primaryKeys))
            ->where($primaryKeys)
            ->limit(1);
        $row = $this->doQuery($stmt)->fetch();
        if ($row) {
            return $this->update($model);
        } else {
            try {
                return $this->insert($model);
            } catch (\PDOException $e) {
                if (SqlState::INTEGRITY_CONSTRAINT_VIOLATION == $e->getCode()) {
                    $this->update($model);
                } else {
                    throw $e;
                }
            }
        }
    }

    /**
     * @param string|array|callable $condition
     *
     * @return bool
     */
    public function delete($condition)
    {
        $modelClass = $this->tableMetadata->getModelClass();
        if ($condition instanceof $modelClass) {
            $condition = $this->getConstraints($condition);
        }
        $stmt = $this->connection->delete($this->tableMetadata->getName());
        $stmt = $this->buildStatement($stmt, $condition, false);

        return $this->doExecute($stmt);
    }

    /**
     * @param array|callable $condition
     *
     * @return int
     */
    public function count($condition = null)
    {
        $stmt = $this->buildCountStatement($condition);
        $count = $this->doQuery($stmt)->fetchColumn(0);
        $stmt->close();

        return $count;
    }

    /**
     * @param string|array|callable $condition
     *
     * @return object|false
     */
    public function findOne($condition)
    {
        $stmt = $this->connection->from($this->tableMetadata->getName())
            ->select($this->tableMetadata->getColumnNames());
        $stmt = $this->buildStatement($stmt, $condition);
        $stmt->limit(1)->offset(0);
        $row = $this->doQuery($stmt)->fetch();
        $stmt->close();

        return $row ? $this->modelTransformer->thaw($row) : false;
    }

    /**
     * @param array|callable|Criteria $condition
     * @param bool                    $returnModel
     *
     * @return object[]|array
     */
    public function query($condition, $returnModel = true)
    {
        $stmt = $this->connection->from($this->tableMetadata->getName())
            ->select($this->tableMetadata->getColumnNames());
        if (!empty($condition)) {
            $this->buildStatement($stmt, $condition, false);
        }
        $stmt = $this->doQuery($stmt);
        if ($returnModel) {
            return array_map(function ($row) {
                return $this->modelTransformer->thaw($row);
            }, $stmt->fetchAll(\PDO::FETCH_ASSOC));
        } else {
            return $stmt->fetchAll(\PDO::FETCH_ASSOC);
        }
    }

    /**
     * @param object $model
     *
     * @return StatementInterface
     */
    protected function buildInsertStatement($model)
    {
        $cols = $this->modelTransformer->freeze($model);

        foreach ($this->tableMetadata->getTimestamps() as $column) {
            if (!isset($cols[$column])) {
                $cols[$column] = $this->now();
                $this->modelTransformer->set($model, $column, $cols[$column]);
            }
        }
        $stmt = $this->connection->insert($this->tableMetadata->getName())
            ->cols($cols);

        return $stmt;
    }

    /**
     * @param object $model
     * @param mixed  $condition
     *
     * @return StatementInterface
     */
    protected function buildUpdateStatement($model, $condition)
    {
        if (null === $condition) {
            $condition = $this->getConstraints($model);
        }
        $cols = $this->modelTransformer->freeze($model);
        $updatedAtColumn = $this->tableMetadata->getTimestampColumn(TableMetadata::UPDATED_AT);
        if ($updatedAtColumn) {
            $cols[$updatedAtColumn] = $this->now();
            $this->modelTransformer->set($model, $updatedAtColumn, $cols[$updatedAtColumn]);
        }
        $stmt = $this->connection->update($this->tableMetadata->getName())
              ->cols($cols);
        if (is_array($condition) && isset($condition[0])) {
            $condition = Arrays::select($cols, $condition);
        }

        return $this->buildStatement($stmt, $condition, false);
    }

    /**
     * @param mixed $condition
     *
     * @return StatementInterface
     */
    protected function buildCountStatement($condition)
    {
        $stmt = $this->connection->from($this->tableMetadata->getName());
        if ($condition) {
            $stmt = $this->buildStatement($stmt, $condition);
        }

        return $stmt->select(['count(*)'])->limit(1)->offset(0)->resetOrderBy();
    }

    /**
     * @param mixed $condition
     * @param bool  $allowScalar
     *
     * @return StatementInterface
     */
    protected function buildStatement(StatementInterface $stmt, $condition, $allowScalar = true)
    {
        if ($condition instanceof Criteria) {
            $this->buildStatementByCriteria($stmt, $condition);
        } elseif ($allowScalar && is_scalar($condition)) {
            if (!$this->tableMetadata->getAutoIncrementColumn()) {
                throw new \InvalidArgumentException(sprintf("Table '%s' does not have id column", $this->tableMetadata->getName()));
            }
            Assert::notEmpty($condition);
            $stmt->where([$this->tableMetadata->getAutoIncrementColumn() => $condition]);
        } elseif (is_array($condition)) {
            if (empty($condition)) {
                throw new \RuntimeException('Condition cannot be empty');
            }
            $stmt->where($condition);
        } elseif (is_callable($condition)) {
            $stmt = $condition($stmt);
        } else {
            throw new \InvalidArgumentException('Expected primary key, Got '.gettype($condition));
        }

        return $stmt;
    }

    protected function buildStatementByCriteria(StatementInterface $stmt, Criteria $criteria)
    {
        $criteria->buildStatement($stmt, $this->tableMetadata->getColumnMap());
    }

    protected function now()
    {
        return Carbon::now()->toDateTimeString();
    }

    protected function getConstraints($model)
    {
        $idColumn = $this->tableMetadata->getAutoIncrementColumn();
        if ($idColumn) {
            $id = $this->modelTransformer->get($model, $idColumn);
            if ($id) {
                return [$idColumn => $id];
            }
        }
        foreach ($this->tableMetadata->getUniqueConstraints() as $uniqueConstraint) {
            $values = [];
            foreach ($uniqueConstraint as $columnName) {
                $value = $this->modelTransformer->get($model, $columnName);
                if (!isset($value)) {
                    break;
                }
                $values[$columnName] = $value;
            }
            if (count($values) == count($uniqueConstraint)) {
                return $values;
            }
        }
        throw new \InvalidArgumentException('Cannot find unique constraints for '.get_class($model));
    }

    /**
     * @return bool
     */
    protected function doExecute(StatementInterface $stmt)
    {
        $this->lastStatement = $stmt;

        return $stmt->execute();
    }

    protected function doQuery(StatementInterface $stmt)
    {
        $this->lastStatement = $stmt;

        return $stmt->query();
    }
}
