<?php

declare(strict_types=1);

namespace kuiper\db\orm;

use Carbon\Carbon;
use kuiper\db\NullValue;
use kuiper\db\orm\annotation\Timestamp;
use kuiper\db\orm\serializer\SerializerRegistry;
use kuiper\reflection\TypeUtils;

class ModelTransformer
{
    /**
     * @var TableMetadata
     */
    private $metadata;

    /**
     * @var SerializerRegistry
     */
    protected $serializers;

    /**
     * ModelTransformer constructor.
     */
    public function __construct(TableMetadata $metadata, SerializerRegistry $serializers)
    {
        $this->metadata = $metadata;
        $this->serializers = $serializers;
    }

    public function freeze($model)
    {
        $values = [];
        foreach ($this->metadata->getColumns() as $column) {
            $value = $this->getValue($column, $model);
            if (!isset($value)) {
                continue;
            }
            if ($value === NullValue::instance()) {
                $value = null;
            }
            $values[$column->getName()] = $value;
        }

        return $values;
    }

    public function thaw($row)
    {
        $modelClass = $this->metadata->getModelClass();
        $model = new $modelClass();
        foreach ($this->metadata->getColumns() as $column) {
            if (!isset($row[$column->getName()])) {
                continue;
            }
            $this->setValue($column, $model, $row[$column->getName()]);
        }

        return $model;
    }

    public function get($model, $columnName)
    {
        return $this->getValue($this->metadata->getColumnByName($columnName), $model);
    }

    public function set($model, $columnName, $value)
    {
        $this->setValue($this->metadata->getColumnByName($columnName), $model, $value);
    }

    protected function getValue(ColumnMetadata $column, $model)
    {
        $value = call_user_func($column->getGetter(), $model);
        if (!isset($value)) {
            return null;
        }
        if ($column->isEnumerable()) {
            $value = $value->value();
        } elseif ('bool' == $column->getValueType()->getName()) {
            $value = $value ? 1 : 0;
        } elseif ($column->getSerializer()) {
            $value = $this->getSerializer($column->getSerializer())->serialize($value, $column);
        } elseif ($value instanceof \DateTime) {
            if ($column->hasAnnotation(Timestamp::class)) {
                $value = $value->getTimestamp();
            } else {
                $value = Carbon::instance($value)->toDateTimeString();
            }
        }

        return $value;
    }

    protected function setValue(ColumnMetadata $column, $model, $value)
    {
        if ($column->isEnumerable()) {
            $value = call_user_func([$column->getValueType()->getName(), 'fromValue'], $value);
        } elseif ('bool' == $column->getValueType()->getName()) {
            $value = $value > 0;
        } elseif ($column->getSerializer()) {
            $value = $this->getSerializer($column->getSerializer())->unserialize($value, $column);
        } elseif (TypeUtils::isClass($column->getValueType())
            && is_a($column->getValueType()->getName(), \DateTime::class, true)) {
            if ($column->hasAnnotation(Timestamp::class)) {
                $value = Carbon::createFromTimestamp($value);
            } else {
                $value = Carbon::parse($value);
            }
        }
        call_user_func($column->getSetter(), $model, $value);
    }

    protected function getSerializer($name)
    {
        return $this->serializers->getSerializer($name);
    }
}
