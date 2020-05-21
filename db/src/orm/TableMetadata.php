<?php

declare(strict_types=1);

namespace kuiper\db\orm;

use kuiper\helper\Text;

class TableMetadata
{
    const CREATED_AT = 'created_at';

    const UPDATED_AT = 'updated_at';

    /**
     * @var string
     */
    private $name;

    /**
     * @var string
     */
    private $repositoryClass;

    /**
     * @var string
     */
    private $modelClass;

    /**
     * @var ColumnMetadata[]
     */
    private $columns = [];

    /**
     * @var array
     */
    private $columnMap = [];

    /**
     * @var string[]
     */
    private $shardBy = [];

    /**
     * @var array
     */
    private $uniqueConstraints = [];

    /**
     * @var array
     */
    private $timestamps = [];

    /**
     * @var string
     */
    private $autoIncrementColumn;

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    public function setName(string $name): TableMetadata
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return string
     */
    public function getRepositoryClass()
    {
        return $this->repositoryClass;
    }

    public function setRepositoryClass(string $repositoryClass): TableMetadata
    {
        $this->repositoryClass = $repositoryClass;

        return $this;
    }

    /**
     * @return ColumnMetadata[]
     */
    public function getColumns(): array
    {
        return $this->columns;
    }

    public function addColumn(ColumnMetadata $column): TableMetadata
    {
        $this->columns[$column->getName()] = $column;

        return $this;
    }

    /**
     * @return string[]
     */
    public function getShardBy(): array
    {
        return $this->shardBy;
    }

    /**
     * @param string[] $shardBy
     */
    public function setShardBy(array $shardBy): TableMetadata
    {
        $this->shardBy = $shardBy;

        return $this;
    }

    public function getUniqueConstraints(): array
    {
        return $this->uniqueConstraints;
    }

    /**
     * @param string[] $columns
     */
    public function addUniqueConstraint(string $name, array $columns): TableMetadata
    {
        $this->uniqueConstraints[$name] = $columns;

        return $this;
    }

    public function getTimestamps(): array
    {
        return $this->timestamps;
    }

    public function getTimestampColumn($timestampType)
    {
        return isset($this->timestamps[$timestampType]) ? $this->timestamps[$timestampType] : null;
    }

    public function setTimestamp(string $timestampType, string $columnName): TableMetadata
    {
        $this->timestamps[$timestampType] = $columnName;

        return $this;
    }

    /**
     * @return string
     */
    public function getAutoIncrementColumn()
    {
        return $this->autoIncrementColumn;
    }

    public function setAutoIncrementColumn(string $autoIncrementColumn): TableMetadata
    {
        $this->autoIncrementColumn = $autoIncrementColumn;

        return $this;
    }

    public function getModelClass(): string
    {
        return $this->modelClass;
    }

    public function setModelClass(string $modelClass): TableMetadata
    {
        $this->modelClass = $modelClass;

        return $this;
    }

    /**
     * @return ColumnMetadata|null
     */
    public function getColumnByName(string $columnName)
    {
        return isset($this->columns[$columnName]) ? $this->columns[$columnName] : null;
    }

    /**
     * @return ColumnMetadata|null
     */
    public function getColumnByProperty(string $propertyName)
    {
        foreach ($this->columns as $column) {
            if ($column->getProperty()->getName() == $propertyName) {
                return $column;
            }
        }

        return null;
    }

    /**
     * @return string[]
     */
    public function getColumnNames(): array
    {
        return array_keys($this->columns);
    }

    /**
     * 获取属性与数据库字段对应关系.
     */
    public function getColumnMap(): array
    {
        if (empty($this->columnMap)) {
            foreach ($this->columns as $columnName => $column) {
                $propertyName = $column->getProperty()->getName();
                $this->columnMap[$propertyName] = $columnName;
                $this->columnMap[Text::uncamelize($propertyName)] = $columnName;
            }
        }

        return $this->columnMap;
    }
}
