<?php

declare(strict_types=1);

namespace kuiper\db;

use InvalidArgumentException;
use kuiper\db\event\AfterPersistEvent;
use kuiper\db\event\BeforePersistEvent;
use kuiper\db\exception\ExecutionFailException;
use kuiper\db\metadata\MetaModelFactoryInterface;
use kuiper\db\metadata\MetaModelInterface;
use PDO;
use Psr\EventDispatcher\EventDispatcherInterface;

abstract class AbstractCrudRepository implements CrudRepositoryInterface
{
    /**
     * @var QueryBuilderInterface
     */
    protected $queryBuilder;

    /**
     * @var MetaModelInterface
     */
    protected $metaModel;

    /**
     * @var DateTimeFactoryInterface
     */
    protected $dateTimeFactory;

    /**
     * @var EventDispatcherInterface
     */
    protected $eventDispatcher;

    public function __construct(QueryBuilderInterface $queryBuilder,
                                MetaModelFactoryInterface $metaModelFactory,
                                DateTimeFactoryInterface $dateTimeFactory,
                                EventDispatcherInterface $eventDispatcher)
    {
        $this->queryBuilder = $queryBuilder;
        $this->dateTimeFactory = $dateTimeFactory;
        $this->metaModel = $metaModelFactory->create(get_class($this));
        $this->eventDispatcher = $eventDispatcher;
    }

    public function insert($entity)
    {
        $this->dispatch(new BeforePersistEvent($this, $entity));
        $stmt = $this->buildInsertStatement($entity);
        $this->doExecute($stmt);

        $autoIncrementColumn = $this->metaModel->getAutoIncrement();
        if ($autoIncrementColumn) {
            $value = $this->metaModel->getValue($entity, $autoIncrementColumn);
            if (!$value) {
                $this->metaModel->setValue($entity, $autoIncrementColumn, $stmt->getConnection()->lastInsertId());
            }
        }
        $this->dispatch(new AfterPersistEvent($this, $entity));

        return $entity;
    }

    public function update($entity)
    {
        $stmt = $this->buildUpdateStatement($entity, $this->metaModel->getUniqueKey($entity));
        $this->doExecute($stmt);

        return $entity;
    }

    public function save($entity)
    {
        $uniqueKey = $this->metaModel->getUniqueKey($entity);
        if ($uniqueKey && $this->count($uniqueKey) > 0) {
            return $this->update($entity);
        }

        return $this->insert($entity);
    }

    public function findById($id)
    {
        return $this->findFirstBy($this->metaModel->idToPrimaryKey($id));
    }

    public function existsById($id): bool
    {
        return $this->count($this->metaModel->idToPrimaryKey($id)) > 0;
    }

    public function findFirstBy($criteria)
    {
        $stmt = $this->buildQueryStatement($criteria)->limit(1)
            ->offset(0);
        $row = $this->doQuery($stmt)->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            return $this->metaModel->thaw($row);
        }

        return null;
    }

    public function findAllById(array $ids): array
    {
        if (empty($ids)) {
            return [];
        }

        return $this->findAllBy($this->createCriteriaById($ids));
    }

    public function findAllBy($criteria = null): array
    {
        $stmt = $this->buildQueryStatement($criteria);

        return array_map([$this->metaModel, 'thaw'], $stmt->query()->fetchAll(PDO::FETCH_ASSOC));
    }

    public function count($criteria = null): int
    {
        $stmt = $this->buildQueryStatement($criteria)
            ->select('count(*)')
            ->limit(1)
            ->offset(0)
            ->orderBy([]);

        return (int) $this->doQuery($stmt)->fetchColumn(0);
    }

    public function query($criteria): array
    {
        return $this->doQuery($this->buildQueryStatement($criteria))
            ->fetchAll(PDO::FETCH_ASSOC);
    }

    public function deleteById($id): void
    {
        $this->deleteAllBy($this->metaModel->idToPrimaryKey($id));
    }

    public function delete($entity): void
    {
        $this->deleteAllBy($this->metaModel->getUniqueKey($entity));
    }

    public function deleteAllById(array $ids): void
    {
        if (empty($ids)) {
            return;
        }
        $this->deleteAllBy($this->createCriteriaById($ids));
    }

    public function deleteAll(array $entities): void
    {
        $this->deleteAllById(array_map([$this->metaModel, 'getId'], $entities));
    }

    public function deleteAllBy($criteria = null): void
    {
        $stmt = $this->buildStatement(
            $this->queryBuilder->delete($this->getTableName()), $criteria
        );
        $this->doExecute($stmt);
    }

    /**
     * @param array|callable|Criteria $criteria
     */
    protected function buildQueryStatement($criteria): StatementInterface
    {
        return $this->buildStatement(
            $this->queryBuilder->from($this->getTableName())
                ->select(...$this->metaModel->getColumnNames()), $criteria
        );
    }

    /**
     * @param object $entity
     */
    protected function buildInsertStatement($entity): StatementInterface
    {
        $this->setCreationTimestamp($entity);
        $this->setUpdateTimestamp($entity);
        $cols = $this->metaModel->freeze($entity);

        return $this->queryBuilder->insert($this->getTableName())
            ->cols($cols);
    }

    /**
     * @param object $entity
     * @param mixed  $condition
     */
    protected function buildUpdateStatement($entity, $condition): StatementInterface
    {
        $this->setUpdateTimestamp($entity);
        $cols = $this->metaModel->freeze($entity);
        $stmt = $this->queryBuilder->update($this->getTableName())
            ->cols($cols);

        return $this->buildStatement($stmt, $condition);
    }

    /**
     * @param array|callable|Criteria $condition
     */
    protected function buildStatement(StatementInterface $stmt, $condition): StatementInterface
    {
        if ($condition instanceof Criteria) {
            $this->buildStatementByCriteria($stmt, $criteria);
        } elseif (is_array($condition)) {
            if (empty($condition)) {
                throw new InvalidArgumentException('Condition cannot be empty');
            }
            $stmt->where($condition);
        } elseif (is_callable($condition)) {
            $stmt = $condition($stmt);
        } else {
            throw new InvalidArgumentException('Expected primary key, Got '.gettype($condition));
        }

        return $stmt;
    }

    protected function buildStatementByCriteria(StatementInterface $stmt, Criteria $criteria): StatementInterface
    {
        return $criteria->buildStatement($stmt);
    }

    protected function createCriteriaById(array $ids): callable
    {
        if (empty($ids)) {
            throw new InvalidArgumentException('id list is empty');
        }

        return function (StatementInterface $stmt) use ($ids) {
            $keys = [];
            foreach ($ids as $id) {
                $keys[] = $this->metaModel->idToPrimaryKey($id);
            }
            $firstKey = $keys[0];
            if (1 === count($firstKey)) {
                $column = array_keys($firstKey)[0];
                $stmt->in($column, array_column($keys, $column));
            } else {
                foreach ($keys as $key) {
                    $stmt->orWhere($key);
                }
            }

            return $stmt;
        };
    }

    protected function dispatch($event): void
    {
        $this->eventDispatcher && $this->eventDispatcher->dispatch($event);
    }

    protected function doExecute(StatementInterface $stmt): void
    {
        $result = $stmt->execute();
        if (false === $result) {
            throw new ExecutionFailException('execution fail');
        }
    }

    protected function doQuery(StatementInterface $stmt): \PDOStatement
    {
        return $stmt->query();
    }

    protected function currentTimeString(): string
    {
        return $this->dateTimeFactory->currentTimeString();
    }

    /**
     * @param object $entity
     */
    protected function setCreationTimestamp($entity): void
    {
        $column = $this->metaModel->getCreationTimestamp();
        if ($column) {
            $value = $this->metaModel->getValue($entity, $column);
            if (!isset($value)) {
                $this->metaModel->setValue($entity, $column, $this->currentTimeString());
            }
        }
    }

    /**
     * @param object $entity
     */
    protected function setUpdateTimestamp($entity): void
    {
        $column = $this->metaModel->getUpdateTimestamp();
        if ($column) {
            $this->metaModel->setValue($entity, $column, $this->currentTimeString());
        }
    }

    protected function getTableName(): string
    {
        return $this->metaModel->getTable();
    }
}
