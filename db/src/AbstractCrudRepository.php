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

use InvalidArgumentException;
use kuiper\db\exception\ExecutionFailException;
use kuiper\db\metadata\MetaModelFactoryInterface;
use kuiper\db\metadata\MetaModelInterface;
use kuiper\swoole\coroutine\Coroutine;
use PDO;
use Psr\EventDispatcher\EventDispatcherInterface;

abstract class AbstractCrudRepository implements CrudRepositoryInterface
{
    private const DB_LAST_STATEMENT = '__dbLastStatement';

    protected readonly MetaModelInterface $metaModel;

    private ?StatementInterface $lastStatement = null;

    public function __construct(
        private                   readonly QueryBuilderInterface $queryBuilder,
        MetaModelFactoryInterface $metaModelFactory,
        private                   readonly DateTimeFactoryInterface $dateTimeFactory,
        private                   readonly EventDispatcherInterface $eventDispatcher)
    {
        $this->metaModel = $metaModelFactory->createFromRepository(get_class($this));
    }

    /**
     * {@inheritdoc}
     */
    public function insert(object $entity): object
    {
        $this->checkEntityClassMatch($entity);
        $stmt = $this->buildInsertStatement($entity);
        $this->doExecute($stmt);

        $autoIncrementColumn = $this->metaModel->getAutoIncrement();
        if (null !== $autoIncrementColumn) {
            $value = $this->metaModel->getValue($entity, $autoIncrementColumn);
            if (null === $value) {
                $this->metaModel->setValue($entity, $autoIncrementColumn, $stmt->getLastInsertId());
            }
        }

        return $entity;
    }

    /**
     * {@inheritdoc}
     */
    public function batchInsert(array $entities): array
    {
        if (empty($entities)) {
            return [];
        }
        if (1 === count($entities)) {
            reset($entities);

            return [$this->insert(current($entities))];
        }
        $stmt = $this->queryBuilder->insert($this->getTableName());
        foreach ($entities as $entity) {
            $this->checkEntityClassMatch($entity);

            $this->setCreationTimestamp($entity);
            $this->setUpdateTimestamp($entity);
            $cols = $this->metaModel->freeze($entity, false);

            $stmt->addRow($cols);
        }
        $stmt->execute();
        $autoIncrementColumn = $this->metaModel->getAutoIncrement();
        if (null !== $autoIncrementColumn) {
            $lastInsertId = $stmt->getLastInsertId();
            foreach ($entities as $entity) {
                $value = $this->metaModel->getValue($entity, $autoIncrementColumn);
                if (null === $value) {
                    $this->metaModel->setValue($entity, $autoIncrementColumn, $lastInsertId++);
                }
            }
        }

        return $entities;
    }

    /**
     * {@inheritdoc}
     */
    public function update(object $entity): object
    {
        $this->checkEntityClassMatch($entity);

        $uniqueKeys = $this->getUniqueKeyValues($entity);
        if (!isset($uniqueKeys)) {
            throw new InvalidArgumentException('Unique key is not set');
        }
        $stmt = $this->buildUpdateStatement($entity, $uniqueKeys);
        $this->doExecute($stmt);

        return $entity;
    }

    /**
     * {@inheritdoc}
     */
    public function batchUpdate(array $entities): array
    {
        if (empty($entities)) {
            return [];
        }
        if (1 === count($entities)) {
            reset($entities);

            return [$this->update(current($entities))];
        }
        $this->doExecute($this->buildBatchUpdateStatement($entities));

        return $entities;
    }

    /**
     * {@inheritdoc}
     */
    public function save(object $entity): object
    {
        $this->checkEntityClassMatch($entity);

        $id = $this->metaModel->getId($entity);
        if (isset($id)) {
            return $this->update($entity);
        }

        return $this->insert($entity);
    }

    public function batchSave(array $entities): array
    {
        $inserts = [];
        $updates = [];
        foreach ($entities as $entity) {
            $this->checkEntityClassMatch($entity);
            $id = $this->metaModel->getId($entity);
            if (isset($id)) {
                $updates[] = $entity;
            } else {
                $inserts[] = $entity;
            }
        }
        $this->batchInsert($inserts);
        $this->batchUpdate($updates);

        return $entities;
    }

    /**
     * {@inheritdoc}
     */
    public function updateBy($criteria, $update): void
    {
        $stmt = $this->queryBuilder->update($this->getTableName());

        $stmt = $this->buildStatement($stmt, $criteria);
        if (is_array($update)) {
            $cols = [];
            foreach ($update as $column => $value) {
                $property = $this->metaModel->getProperty($column);
                if (null !== $property) {
                    foreach ($property->getColumnValues($value) as $name => $columnValue) {
                        $cols[$name] = $columnValue;
                    }
                } else {
                    $cols[$column] = $value;
                }
            }
            $stmt->cols($cols);
        } elseif (is_callable($update)) {
            $stmt = $update($stmt);
        } else {
            throw new InvalidArgumentException('Expected array or callable, got ' . gettype($update));
        }
        $this->doExecute($stmt);
    }

    /**
     * {@inheritdoc}
     */
    public function findById(mixed $id): ?object
    {
        return $this->findFirstBy($this->metaModel->idToPrimaryKey($id));
    }

    /**
     * {@inheritdoc}
     */
    public function existsById(mixed $id): bool
    {
        return $this->count($this->metaModel->idToPrimaryKey($id)) > 0;
    }

    /**
     * {@inheritdoc}
     */
    public function findFirstBy(mixed $criteria): ?object
    {
        $stmt = $this->buildQueryStatement($criteria)->limit(1)
            ->offset(0);
        $row = $this->doQuery($stmt)->fetch(PDO::FETCH_ASSOC);
        if (!empty($row)) {
            return $this->metaModel->thaw($row);
        }

        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function findByNaturalId(object $example): ?object
    {
        $this->checkEntityClassMatch($example);

        $criteria = $this->metaModel->getNaturalIdValues($example);
        if (!isset($criteria)) {
            throw new InvalidArgumentException('Cannot extract unique constraint from input');
        }

        return $this->findFirstBy($criteria);
    }

    /**
     * {@inheritdoc}
     */
    public function findAllByNaturalId(array $examples): array
    {
        if (empty($examples)) {
            return [];
        }
        $values = [];
        foreach ($examples as $example) {
            $this->checkEntityClassMatch($example);
            $criteria = $values[] = $this->metaModel->getNaturalIdValues($example);
            if (!isset($criteria)) {
                throw new InvalidArgumentException('Cannot extract unique constraint from input');
            }
        }
        // 不能直接使用 Criteria 对象，因为  criteria 对象会被 filterCriteria 进行值转换
        return $this->findAllBy(function ($stmt) use ($values): StatementInterface {
            $naturalIdIndex = $this->metaModel->getNaturalIdIndex();
            if (null !== $naturalIdIndex) {
                $stmt->useIndex($naturalIdIndex);
            }

            return Criteria::create()
                ->matches($values, array_keys($values[0]))
                ->buildStatement($stmt);
        });
    }

    /**
     * {@inheritdoc}
     */
    public function findAllById(array $ids): array
    {
        if (empty($ids)) {
            return [];
        }

        return $this->findAllBy($this->createCriteriaById($ids));
    }

    /**
     * {@inheritdoc}
     */
    public function findAllBy($criteria): array
    {
        $stmt = $this->buildQueryStatement($criteria);

        return array_map([$this->metaModel, 'thaw'], $this->doQuery($stmt)->fetchAll(PDO::FETCH_ASSOC));
    }

    /**
     * {@inheritdoc}
     */
    public function count($criteria): int
    {
        $stmt = $this->buildQueryStatement($criteria)
            ->select('count(*)')
            ->limit(1)
            ->offset(0)
            ->orderBy([]);

        return (int)$this->doQuery($stmt)->fetchColumn();
    }

    /**
     * {@inheritdoc}
     */
    public function query($criteria): array
    {
        return $this->doQuery($this->buildQueryStatement($criteria))
            ->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * {@inheritdoc}
     */
    public function deleteById($id): void
    {
        $this->deleteAllBy($this->metaModel->idToPrimaryKey($id));
    }

    /**
     * {@inheritdoc}
     */
    public function delete($entity): void
    {
        $this->checkEntityClassMatch($entity);

        $uniqueKeys = $this->getUniqueKeyValues($entity);
        if (!isset($uniqueKeys)) {
            throw new InvalidArgumentException('missing unique key');
        }
        $this->deleteAllBy($uniqueKeys);
    }

    /**
     * {@inheritdoc}
     */
    public function deleteFirstBy($criteria): void
    {
        $stmt = $this->buildStatement(
            $this->queryBuilder->delete($this->getTableName()), $criteria
        );
        $stmt->limit(1);
        $this->doExecute($stmt);
    }

    /**
     * {@inheritdoc}
     */
    public function deleteAllById(array $ids): void
    {
        if (empty($ids)) {
            return;
        }
        $this->deleteAllBy($this->createCriteriaById($ids));
    }

    /**
     * {@inheritdoc}
     */
    public function deleteAll(array $entities): void
    {
        $this->deleteAllById(array_map([$this->metaModel, 'getId'], $entities));
    }

    /**
     * {@inheritdoc}
     */
    public function deleteAllBy($criteria): void
    {
        $stmt = $this->buildStatement(
            $this->queryBuilder->delete($this->getTableName()), $criteria
        );
        $this->doExecute($stmt);
    }

    public function getQueryBuilder(): QueryBuilderInterface
    {
        return $this->queryBuilder;
    }

    public function getMetaModel(): MetaModelInterface
    {
        return $this->metaModel;
    }

    protected function buildQueryStatement(mixed $criteria): StatementInterface
    {
        return $this->buildStatement(
            $this->queryBuilder->from($this->getTableName())
                ->select(...$this->metaModel->getColumnNames()),
            $criteria
        );
    }

    protected function buildInsertStatement(object $entity): StatementInterface
    {
        $this->setCreationTimestamp($entity);
        $this->setUpdateTimestamp($entity);
        $cols = $this->metaModel->freeze($entity);

        return $this->queryBuilder->insert($this->getTableName())
            ->cols($cols);
    }

    protected function buildUpdateStatement(object $entity, mixed $condition): StatementInterface
    {
        $this->setUpdateTimestamp($entity);
        $cols = $this->metaModel->freeze($entity);
        foreach ($this->metaModel->getIdValues($entity) as $idColumnName => $value) {
            unset($cols[$idColumnName]);
        }
        $stmt = $this->queryBuilder->update($this->getTableName())
            ->cols($cols);

        return $this->buildStatement($stmt, $condition);
    }

    protected function buildStatement(StatementInterface $stmt, mixed $condition): StatementInterface
    {
        if ($condition instanceof Criteria) {
            $this->buildStatementByCriteria($stmt, $this->metaModel->filterCriteria($condition));
        } elseif (is_array($condition)) {
            if (empty($condition)) {
                throw new InvalidArgumentException('Condition cannot be empty');
            }
            $stmt->where($condition);
        } elseif (is_callable($condition)) {
            $stmt = $condition($stmt);
        } else {
            throw new InvalidArgumentException('Expected primary key, Got ' . gettype($condition));
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

        return function (StatementInterface $stmt) use ($ids): StatementInterface {
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

    protected function dispatch(object $event): void
    {
        $this->eventDispatcher->dispatch($event);
    }

    protected function doExecute(StatementInterface $stmt): void
    {
        $this->setLastStatement($stmt);
        $result = $stmt->execute();
        if (false === $result) {
            throw new ExecutionFailException('execution fail');
        }
    }

    private function setLastStatement(StatementInterface $stmt): void
    {
        if (class_exists(Coroutine::class)) {
            Coroutine::getContext()[self::DB_LAST_STATEMENT] = $stmt;
        } else {
            $this->lastStatement = $stmt;
        }
    }

    public function getLastStatement(): ?StatementInterface
    {
        if (class_exists(Coroutine::class)) {
            return Coroutine::getContext()[self::DB_LAST_STATEMENT] ?? null;
        }

        return $this->lastStatement;
    }

    protected function doQuery(StatementInterface $stmt): StatementInterface
    {
        $this->setLastStatement($stmt);

        return $stmt->query();
    }

    protected function currentTimeString(): string
    {
        return $this->dateTimeFactory->currentTimeString();
    }

    protected function setCreationTimestamp(object $entity): void
    {
        $column = $this->metaModel->getCreationTimestamp();
        if (null !== $column) {
            $value = $this->metaModel->getValue($entity, $column);
            if (!isset($value)) {
                $this->metaModel->setValue($entity, $column, $this->currentTimeString());
            }
        }
    }

    protected function setUpdateTimestamp(object $entity): void
    {
        $column = $this->metaModel->getUpdateTimestamp();
        if (null !== $column) {
            $this->metaModel->setValue($entity, $column, $this->currentTimeString());
        }
    }

    protected function getUniqueKeyValues(object $entity): ?array
    {
        return $this->metaModel->getIdValues($entity)
            ?? $this->metaModel->getNaturalIdValues($entity);
    }

    protected function getTableName(): string
    {
        return $this->metaModel->getTable();
    }

    protected function checkEntityClassMatch(object $entity): void
    {
        if (!$this->metaModel->getEntityClass()->isInstance($entity)) {
            throw new InvalidArgumentException('Expected entity instance of ' . $this->metaModel->getEntityClass()->getName() . ', got ' . get_class($entity));
        }
    }

    protected function buildBatchUpdateStatement(array $entities): StatementInterface
    {
        [$idColumn, $idValues] = $this->extractIdValues($entities);
        $stmt = $this->queryBuilder->update($this->getTableName());
        $fields = [];
        $rows = [];
        $bindValues = [];
        foreach ($entities as $i => $entity) {
            $this->setUpdateTimestamp($entity);
            $cols = $this->metaModel->freeze($entity);
            unset($cols[$idColumn]);
            if (empty($fields)) {
                $fields = array_keys($cols);
            }
            $rows[$idValues[$i]] = $cols;
        }

        $i = 1;
        foreach ($fields as $field) {
            $caseExp = ["case `$idColumn`"];
            foreach ($idValues as $idValue) {
                $v1 = 'i' . ($i++);
                $v2 = 'i' . ($i++);
                $caseExp[] = sprintf('when :%s then :%s', $v1, $v2);
                $bindValues[$v1] = $idValue;
                $bindValues[$v2] = $rows[$idValue][$field] ?? null;
            }
            $stmt->set($field, implode(' ', $caseExp) . ' end');
        }
        $stmt->bindValues($bindValues);
        $stmt->in($idColumn, $idValues);

        return $stmt;
    }

    protected function extractIdValues(array $entities): array
    {
        $idValues = [];
        $idColumn = '';
        foreach ($entities as $i => $entity) {
            $this->checkEntityClassMatch($entity);
            $id = $this->metaModel->getId($entity);
            if (!isset($id)) {
                throw new InvalidArgumentException('entity id should not empty');
            }
            $idColumns = $this->metaModel->idToPrimaryKey($id);
            if (empty($idColumn)) {
                if (1 !== count($idColumns)) {
                    throw new InvalidArgumentException('Cannot batch update for primary key that contain multiple columns');
                }
                $idColumn = array_keys($idColumns)[0];
            }
            $idValues[$i] = $idColumns[$idColumn];
        }

        return [$idColumn, $idValues];
    }
}
