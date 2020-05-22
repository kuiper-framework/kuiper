<?php

declare(strict_types=1);

namespace kuiper\db\orm;

use kuiper\annotations\DocReaderInterface;
use kuiper\annotations\ReaderInterface;
use kuiper\db\annotation\Column;
use kuiper\db\annotation\CreationTimestamp;
use kuiper\db\exception\ConfigurationException;
use kuiper\db\orm\annotation\Annotation;
use kuiper\db\orm\annotation\Entity;
use kuiper\db\orm\annotation\Enum;
use kuiper\db\orm\annotation\Id;
use kuiper\db\orm\annotation\Serializer;
use kuiper\db\orm\annotation\Table;
use kuiper\db\orm\annotation\UpdatedAt;
use kuiper\db\orm\serializer\SerializerRegistry;
use kuiper\helper\Text;

/**
 * Class TableMetadataFactory.
 */
class TableMetadataFactory
{
    /**
     * @var ReaderInterface
     */
    private $annotationReader;
    /**
     * @var DocReaderInterface
     */
    private $docReader;

    /**
     * @var array
     */
    private $cache;

    private static $ANNOTATION_HANDLERS = [
        Table::class => 'processTableAnnotation',
        Entity::class => 'processEntityAnnotation',
        CreationTimestamp::class => 'processCreatedAtAnnotation',
        UpdatedAt::class => 'processUpdatedAtAnnotation',
        Enum::class => 'processEnumAnnotation',
        Id::class => 'processIdAnnotation',
        Serializer::class => 'processSerializerAnnotation',
    ];
    /**
     * @var SerializerRegistry
     */
    private $serializers;

    /**
     * @var string
     */
    private $tableNamePrefix;

    /**
     * TableMetadataFactory constructor.
     *
     * @param string $tableNamePrefix
     */
    public function __construct(ReaderInterface $annotationReader, DocReaderInterface $docReader, SerializerRegistry $serializers, $tableNamePrefix = '')
    {
        $this->annotationReader = $annotationReader;
        $this->docReader = $docReader;
        $this->serializers = $serializers;
        $this->tableNamePrefix = $tableNamePrefix;
    }

    /**
     * @return TableMetadata
     */
    public function create(string $modelClass)
    {
        if ($this->isCacheHit($modelClass)) {
            return $this->getCached($modelClass);
        }
        $class = new \ReflectionClass($modelClass);
        $metadata = new TableMetadata();
        $metadata->setModelClass($modelClass);
        $metadata->setName($this->tableNamePrefix.Text::uncamelize($class->getShortName()));
        $this->processClassAnnotations($metadata, $class);
        $this->processPropertyAnnotations($metadata, $class);

        return $this->save($modelClass, $metadata);
    }

    public function clearCache(string $className = null)
    {
        if ($className) {
            unset($this->cache[$className]);
        } else {
            $this->cache = [];
        }

        return $this;
    }

    private function isCacheHit(string $className)
    {
        return isset($this->cache[$className]);
    }

    private function getCached(string $className)
    {
        return $this->cache[$className];
    }

    private function save(string $className, TableMetadata $metadata)
    {
        return $this->cache[$className] = $metadata;
    }

    private function processClassAnnotations(TableMetadata $metadata, \ReflectionClass $class)
    {
        foreach ($this->annotationReader->getClassAnnotations($class) as $annotation) {
            if ($annotation instanceof Annotation && isset(self::$ANNOTATION_HANDLERS[get_class($annotation)])) {
                $handler = self::$ANNOTATION_HANDLERS[get_class($annotation)];
                $this->$handler($metadata, $annotation);
            }
        }
    }

    private function processPropertyAnnotations(TableMetadata $metadata, \ReflectionClass $class)
    {
        foreach ($class->getProperties() as $property) {
            /** @var Column $columnAnnotation */
            $columnAnnotation = $this->annotationReader->getPropertyAnnotation($property, Column::class);
            if (!$columnAnnotation) {
                continue;
            }
            $column = $this->processColumnAnnotation($metadata, $property, $columnAnnotation);
            $column->setValueType($this->docReader->getPropertyType($property));

            foreach ($this->annotationReader->getPropertyAnnotations($property) as $annotation) {
                if ($annotation instanceof Annotation) {
                    if (isset(self::$ANNOTATION_HANDLERS[get_class($annotation)])) {
                        $handler = self::$ANNOTATION_HANDLERS[get_class($annotation)];
                        $this->$handler($metadata, $column, $annotation);
                    } else {
                        $column->addAnnotation($annotation);
                    }
                }
            }
        }
    }

    /**  @noinspection PhpUnusedPrivateMethodInspection */
    private function processTableAnnotation(TableMetadata $metadata, Table $annotation)
    {
        if ($annotation->name) {
            $prefix = isset($annotation->prefix) ? $annotation->prefix : $this->tableNamePrefix;
            $metadata->setName($prefix.$annotation->name);
        }
        if ($annotation->shardBy) {
            $metadata->setShardBy($annotation->shardBy);
        }
        if ($annotation->uniqueConstraints) {
            foreach ($annotation->uniqueConstraints as $constraint) {
                $metadata->addUniqueConstraint($constraint->name, $constraint->columns);
            }
        }
    }

    /**  @noinspection PhpUnusedPrivateMethodInspection */
    private function processEntityAnnotation(TableMetadata $metadata, Entity $annotation)
    {
        if ($annotation->repositoryClass) {
            $metadata->setRepositoryClass($annotation->repositoryClass);
        }
    }

    private function processColumnAnnotation(TableMetadata $metadata, \ReflectionProperty $property, Column $annotation)
    {
        $column = new ColumnMetadata();
        $column->setModelClass($metadata->getModelClass());
        $column->setName($annotation->name ?: Text::uncamelize($property->getName()));
        $column->setProperty($property);
        if ($annotation->type) {
            $column->setType($annotation->type);
        }
        if ($annotation->length) {
            $column->setLength($annotation->length);
        }
        if ($annotation->nullable) {
            $column->setNullable($annotation->nullable);
        }
        $metadata->addColumn($column);

        return $column;
    }

    private function processCreatedAtAnnotation(TableMetadata $metadata, ColumnMetadata $column, CreationTimestamp $annotation)
    {
        $metadata->setTimestamp(TableMetadata::CREATED_AT, $column->getName());
    }

    private function processUpdatedAtAnnotation(TableMetadata $metadata, ColumnMetadata $column, UpdatedAt $annotation)
    {
        $metadata->setTimestamp(TableMetadata::UPDATED_AT, $column->getName());
    }

    private function processEnumAnnotation(TableMetadata $metadata, ColumnMetadata $column, Enum $annotation)
    {
        $column->setEnumerable(true);
    }

    private function processIdAnnotation(TableMetadata $metadata, ColumnMetadata $column)
    {
        $metadata->setAutoIncrementColumn($column->getName());
    }

    private function processSerializerAnnotation(TableMetadata $metadata, ColumnMetadata $column, Serializer $serializer)
    {
        if (!$this->serializers->hasSerializer($serializer->name)) {
            throw new ConfigurationException(sprintf("Unknown serializer '%s' for property %s->%s", $serializer->name, $column->getProperty()->getDeclaringClass(), $column->getProperty()->getName()));
        }
        $column->setSerializer($serializer->name);
    }
}
