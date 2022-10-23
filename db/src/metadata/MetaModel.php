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

namespace kuiper\db\metadata;

use InvalidArgumentException;
use kuiper\db\attribute\CreationTimestamp;
use kuiper\db\attribute\Id;
use kuiper\db\attribute\NaturalId;
use kuiper\db\attribute\UpdateTimestamp;
use kuiper\db\Criteria;
use kuiper\db\criteria\CriteriaFilterInterface;
use kuiper\db\criteria\MetaModelCriteriaFilter;
use kuiper\helper\Arrays;
use ReflectionClass;

class MetaModel implements MetaModelInterface
{
    /**
     * @var MetaModelProperty[]
     */
    private array $properties = [];

    /**
     * @var Column[]
     */
    private array $columns = [];

    /**
     * @var array
     */
    private array $annotatedColumns = [];

    private ?MetaModelProperty $idProperty = null;

    private ?MetaModelCriteriaFilter $expressionClauseFilter = null;

    private ?array $columnAlias = null;

    private ?string $naturalIdIndex = null;

    public function __construct(
        private readonly string $table,
        private readonly ReflectionClass $entityClass,
        array $properties)
    {
        /** @var MetaModelProperty $property */
        foreach ($properties as $property) {
            $this->properties[$property->getName()] = $property;
            if ($property->hasAttribute(Id::class)) {
                $this->idProperty = $property;
            }
            if ($property->hasAttribute(NaturalId::class)) {
                /** @var NaturalId $naturalIdAnnotation */
                $naturalIdAnnotation = $property->getAttribute(NaturalId::class);
                if (!empty($naturalIdAnnotation->getName())) {
                    $this->naturalIdIndex = $naturalIdAnnotation->getName();
                }
            }
            foreach ($property->getColumns() as $column) {
                $this->columns[$column->getName()] = $column;
                if ($column->hasId()) {
                    $this->annotatedColumns[Id::class][] = $column;
                } elseif ($column->hasNaturalId()) {
                    $this->annotatedColumns[NaturalId::class][] = $column;
                }
                if ($column->hasCreationTimestamp()) {
                    $this->annotatedColumns[CreationTimestamp::class] = $column;
                } elseif ($column->hasUpdateTimestamp()) {
                    $this->annotatedColumns[UpdateTimestamp::class] = $column;
                }
            }
        }
        if (!isset($this->idProperty)) {
            throw new InvalidArgumentException($entityClass->getName().' does not contain id');
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getTable(): string
    {
        return $this->table;
    }

    /**
     * {@inheritdoc}
     */
    public function getEntityClass(): ReflectionClass
    {
        return $this->entityClass;
    }

    protected function checkEntityMatch(object $entity): void
    {
        if (!$this->getEntityClass()->isInstance($entity)) {
            throw new InvalidArgumentException("Expected {$this->getEntityClass()->getName()}, got ".get_class($entity));
        }
    }

    /**
     * {@inheritdoc}
     */
    public function freeze(object $entity, bool $ignoreNull = true): array
    {
        $this->checkEntityMatch($entity);
        $columnValues = [];
        foreach ($this->columns as $name => $column) {
            $value = $column->getValue($entity);
            if ($this->isNull($value)) {
                $columnValues[$name] = null;
            } elseif (!$ignoreNull || isset($value)) {
                $columnValues[$name] = $value;
            }
        }

        return $columnValues;
    }

    /**
     * {@inheritdoc}
     */
    public function thaw(array $columnValues): object
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
    public function getValue($entity, string $columnName): mixed
    {
        $this->checkEntityMatch($entity);

        return $this->columns[$columnName]->getValue($entity);
    }

    /**
     * {@inheritdoc}
     */
    public function setValue($entity, string $columnName, $value): void
    {
        $this->checkEntityMatch($entity);
        $this->columns[$columnName]->setValue($entity, $value);
    }

    /**
     * {@inheritdoc}
     */
    public function getCreationTimestamp(): ?string
    {
        return isset($this->annotatedColumns[CreationTimestamp::class])
            ? $this->annotatedColumns[CreationTimestamp::class]->getName() : null;
    }

    /**
     * {@inheritdoc}
     */
    public function getUpdateTimestamp(): ?string
    {
        return isset($this->annotatedColumns[UpdateTimestamp::class])
            ? $this->annotatedColumns[UpdateTimestamp::class]->getName() : null;
    }

    /**
     * {@inheritdoc}
     */
    public function getNaturalIdIndex(): ?string
    {
        return $this->naturalIdIndex;
    }

    /**
     * {@inheritdoc}
     */
    public function idToPrimaryKey($id): array
    {
        return $this->idProperty->getColumnValues($id);
    }

    /**
     * {@inheritdoc}
     */
    public function getAutoIncrement(): ?string
    {
        /** @var Column[] $idColumns */
        $idColumns = $this->annotatedColumns[Id::class];
        if (1 === count($idColumns) && $idColumns[0]->hasGeneratedValue()) {
            return $idColumns[0]->getName();
        }

        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function getIdValues(object $entity): ?array
    {
        $this->checkEntityMatch($entity);

        return $this->getUniqueKeyValues($entity, Id::class);
    }

    /**
     * {@inheritdoc}
     */
    public function getNaturalIdValues(object $entity): ?array
    {
        $this->checkEntityMatch($entity);

        return $this->getUniqueKeyValues($entity, NaturalId::class);
    }

    /**
     * {@inheritdoc}
     */
    public function getUniqueKey(object $entity, string $joiner = "\0x1", bool $ignoreCase = true): string
    {
        $this->checkEntityMatch($entity);
        $values = $this->getUniqueKeyValues($entity, NaturalId::class);
        if (null === $values) {
            throw new InvalidArgumentException($this->getEntityClass()->getName().' does not has natural id');
        }

        $key = implode($joiner, $values);

        return $ignoreCase ? strtolower($key) : $key;
    }

    protected function getUniqueKeyValues(object $entity, string $idAnnotation): ?array
    {
        if (!isset($this->annotatedColumns[$idAnnotation])) {
            return null;
        }
        $values = $this->getColumnValues($entity, $this->annotatedColumns[$idAnnotation]);
        $nonNullValues = Arrays::filter($values);
        if (empty($nonNullValues)) {
            return null;
        }
        if (count($nonNullValues) !== count($values)) {
            $nullKeys = array_filter(array_keys($values), static function ($key) use ($values): bool {
                return !isset($values[$key]);
            });
            throw new InvalidArgumentException('Entity contains null value in unique key columns: '.implode(',', $nullKeys));
        }

        return $values;
    }

    /**
     * {@inheritdoc}
     */
    public function getId(object $entity)
    {
        $this->checkEntityMatch($entity);

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
     * {@inheritdoc}
     */
    public function getColumns(): array
    {
        return array_values($this->columns);
    }

    /**
     * {@inheritdoc}
     */
    public function getProperty(string $propertyPath): ?MetaModelProperty
    {
        $parts = explode(MetaModelProperty::PATH_SEPARATOR, $propertyPath, 2);
        if (!isset($this->properties[$parts[0]])) {
            return null;
        }
        if (1 === count($parts)) {
            return $this->properties[$propertyPath] ?? null;
        }

        return $this->properties[$parts[0]]->getSubProperty($parts[1]);
    }

    /**
     * {@inheritdoc}
     */
    public function filterCriteria(Criteria $criteria): Criteria
    {
        return $criteria->filter($this->getCriteriaFilter())
            ->alias($this->getColumnAlias());
    }

    /**
     * @param object   $entity
     * @param Column[] $columns
     */
    protected function getColumnValues(object $entity, array $columns): ?array
    {
        $values = [];
        foreach ($columns as $column) {
            $values[$column->getName()] = $column->getValue($entity);
        }

        return $values;
    }

    /**
     * @throws \ReflectionException
     */
    protected function createEntity(): object
    {
        return $this->entityClass->newInstanceWithoutConstructor();
    }

    protected function isNull(mixed $value): bool
    {
        return $value instanceof NullValue;
    }

    protected function getCriteriaFilter(): CriteriaFilterInterface
    {
        if (null === $this->expressionClauseFilter) {
            $this->expressionClauseFilter = new MetaModelCriteriaFilter($this);
        }

        return $this->expressionClauseFilter;
    }

    protected function getColumnAlias(): array
    {
        if (null === $this->columnAlias) {
            $this->columnAlias = [];
            foreach ($this->getColumns() as $column) {
                $this->columnAlias[$column->getPropertyPath()] = $column->getName();
            }
        }

        return $this->columnAlias;
    }
}
