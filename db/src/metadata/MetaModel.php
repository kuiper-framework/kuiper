<?php

declare(strict_types=1);

namespace kuiper\db\metadata;

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
     * @var Column[]
     */
    private $columns;

    /**
     * @var Column
     */
    private $idColumn;

    /**
     * @var Column[]
     */
    private $naturalIdColumns;

    /**
     * @var Column
     */
    private $creationTimestampColumn;

    /**
     * @var Column
     */
    private $updateTimestampColumn;

    public function __construct(string $table, string $entityClass, array $columns)
    {
        $this->table = $table;
        $this->entityClass = $entityClass;
        /** @var Column $column */
        foreach ($columns as $column) {
            if ($column->isId()) {
                $this->idColumn = $column;
            } elseif ($column->isNaturalId()) {
                $this->naturalIdColumns[$column->getName()] = $column;
            }
            if ($column->isCreationTimestamp()) {
                $this->creationTimestampColumn = $column;
            } elseif ($column->isUpdateTimestamp()) {
                $this->updateTimestampColumn = $column;
            }
            $this->columns[$column->getName()] = $column;
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

    public function idToPrimaryKey($id): array
    {
        if (is_object($id)) {
            $entity = $this->createEntity();
            $this->idColumn->getProperty()->setValue($id);

            return $this->getUniqueKey($entity);
        }

        return [$this->idColumn->getName() => $id];
    }

    public function getCreationTimestamp(): ?string
    {
        return $this->creationTimestampColumn ? $this->creationTimestampColumn->getName() : null;
    }

    public function getUpdateTimestamp(): ?string
    {
        return $this->updateTimestampColumn ? $this->updateTimestampColumn->getName() : null;
    }

    public function getAutoIncrement(): ?string
    {
        return $this->idColumn->isGeneratedValue() ? $this->idColumn->getName() : null;
    }

    public function getUniqueKey($entity): array
    {
        $idValue = $this->idColumn->getValue($entity);
        if (isset($idValue)) {
            return [$this->idColumn->getName() => $idValue];
        }
        if ($this->naturalIdColumns) {
            return array_map(static function (Column $column) use ($entity) {
                return $column->getValue($entity);
            }, $this->naturalIdColumns);
        }
        throw new \InvalidArgumentException('entity id column is not set');
    }

    public function getId($entity)
    {
        return $this->idColumn->getPropertyValue($entity);
    }

    /**
     * {@inheritdoc}
     */
    public function getColumnNames(): array
    {
        return array_keys($this->columns);
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
        return $value === NullValue::instance();
    }
}
