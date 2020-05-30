<?php

declare(strict_types=1);

namespace kuiper\db\metadata;

use kuiper\db\annotation\CreationTimestamp;
use kuiper\db\annotation\Id;
use kuiper\db\annotation\NaturalId;
use kuiper\db\annotation\UpdateTimestamp;

class MetaModel implements MetaModelInterface
{
    /**
     * @var string
     */
    private $table;

    /**
     * @var string
     */
    private $entityClass;

    /**
     * @var MetaModelProperty[]
     */
    private $properties;

    /**
     * @var Column[]
     */
    private $columns;

    /**
     * @var array
     */
    private $annotatedColumns;

    /**
     * @var MetaModelProperty
     */
    private $idProperty;

    public function __construct(string $table, string $entityClass, array $properties)
    {
        $this->table = $table;
        $this->entityClass = $entityClass;
        $this->properties = $properties;
        /** @var MetaModelProperty $property */
        foreach ($properties as $property) {
            if ($property->hasAnnotation(Id::class)) {
                $this->idProperty = $property;
            }
            foreach ($property->getColumns() as $column) {
                $this->columns[$column->getName()] = $column;
                if ($column->isId()) {
                    $this->annotatedColumns[Id::class][] = $column;
                } elseif ($column->isNaturalId()) {
                    $this->annotatedColumns[NaturalId::class][] = $column;
                }
                if ($column->isCreationTimestamp()) {
                    $this->annotatedColumns[CreationTimestamp::class] = $column;
                } elseif ($column->isUpdateTimestamp()) {
                    $this->annotatedColumns[UpdateTimestamp::class] = $column;
                }
            }
        }
        if (!isset($this->idProperty)) {
            throw new \InvalidArgumentException($entityClass.' does not contain id');
        }
    }

    public function getTable(): string
    {
        return $this->table;
    }

    public function freeze($entity): array
    {
        $columnValues = [];
        foreach ($this->columns as $name => $column) {
            $value = $column->getValue($entity);
            if ($this->isNull($value)) {
                $columnValues[$name] = null;
            } elseif (isset($value)) {
                $columnValues[$name] = $value;
            }
        }

        return $columnValues;
    }

    /**
     * {@inheritdoc}
     */
    public function thaw(array $columnValues)
    {
        $entity = $this->createEntity();
        foreach ($columnValues as $column => $value) {
            if (isset($this->columns[$column], $value)) {
                $this->columns[$column]->setValue($entity, $value);
            }
        }

        return $entity;
    }

    /**
     * {@inheritdoc}
     */
    public function getValue($entity, string $columnName)
    {
        return $this->columns[$columnName]->getValue($entity);
    }

    public function setValue($entity, string $columnName, $value): void
    {
        $this->columns[$columnName]->setValue($entity, $value);
    }

    public function getCreationTimestamp(): ?string
    {
        return isset($this->annotatedColumns[CreationTimestamp::class])
            ? $this->annotatedColumns[CreationTimestamp::class]->getName() : null;
    }

    public function getUpdateTimestamp(): ?string
    {
        return isset($this->annotatedColumns[UpdateTimestamp::class])
            ? $this->annotatedColumns[UpdateTimestamp::class]->getName() : null;
    }

    public function idToPrimaryKey($id): array
    {
        /** @var Column[] $idColumns */
        $idColumns = $this->annotatedColumns[Id::class];
        if (is_object($id) || count($idColumns) > 1) {
            $entity = $this->createEntity();
            $this->idProperty->setValue($entity, $id);

            return $this->getColumnValues($entity, $idColumns);
        }

        return [$idColumns[0]->getName() => $id];
    }

    public function getAutoIncrement(): ?string
    {
        /** @var Column[] $idColumns */
        $idColumns = $this->annotatedColumns[Id::class];
        if (1 === count($idColumns) && $idColumns[0]->isGeneratedValue()) {
            return $idColumns[0]->getName();
        }

        return null;
    }

    public function getUniqueKey($entity): array
    {
        $keys = [];
        $idValue = $this->idProperty->getValue($entity);
        if (isset($idValue)) {
            return $this->getColumnValues($entity, $this->annotatedColumns[Id::class]);
        }
        if (isset($this->annotatedColumns[NaturalId::class])) {
            return $this->getColumnValues($entity, $this->annotatedColumns[NaturalId::class]);
        }
        throw new \InvalidArgumentException('entity id column is not set');
    }

    public function getId($entity)
    {
        return $this->idProperty->getValue($entity);
    }

    /**
     * {@inheritdoc}
     */
    public function getColumnNames(): array
    {
        return array_keys($this->columns);
    }

    /**
     * @param object   $entity
     * @param Column[] $columns
     */
    protected function getColumnValues($entity, array $columns): array
    {
        $values = [];
        foreach ($columns as $column) {
            $values[$column->getName()] = $column->getValue($entity);
        }

        return $values;
    }

    /**
     * @return mixed
     */
    protected function createEntity()
    {
        $entityClass = $this->entityClass;

        return new $entityClass();
    }

    protected function isNull($value): bool
    {
        return $value instanceof NullValue;
    }
}
