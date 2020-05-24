<?php

declare(strict_types=1);

namespace kuiper\db\metadata;

use kuiper\annotations\AnnotationReaderInterface;
use kuiper\db\annotation\Entity;
use kuiper\db\annotation\Table;

class MetaModelFactory implements MetaModelFactoryInterface
{
    /**
     * @var AnnotationReaderInterface
     */
    private $annotationReader;
    /**
     * @var NamingStrategyInterface
     */
    private $namingStrategy;

    public function __construct(AnnotationReaderInterface $annotationReader, NamingStrategyInterface $namingStrategy)
    {
        $this->annotationReader = $annotationReader;
        $this->namingStrategy = $namingStrategy;
    }

    /**
     * {@inheritdoc}
     */
    public function create($repository): MetaModelInterface
    {
        $reflectionClass = new \ReflectionClass($repository);
        $entityClass = $this->getEntityClass($reflectionClass);

        return new MetaModel($this->getTable($entityClass), $entityClass->getName(), $this->getColumns($entityClass));
    }

    private function getEntityClass(\ReflectionClass $reflectionClass): \ReflectionClass
    {
        /** @var Entity $annotation */
        $annotation = $this->annotationReader->getClassAnnotation($reflectionClass, Entity::class);
        if (!$annotation) {
            foreach ($reflectionClass->getInterfaces() as $interface) {
                $annotation = $this->annotationReader->getClassAnnotation($interface, Entity::class);
                if ($annotation) {
                    break;
                }
            }
        }
        if (!$annotation) {
            throw new \InvalidArgumentException($reflectionClass->getName().' should annotation with @'.Entity::class);
        }

        return new \ReflectionClass($annotation->value);
    }

    private function getTable(\ReflectionClass $entityClass): string
    {
        $context = new NamingContext();
        /** @var Table $annotation */
        $annotation = $this->annotationReader->getClassAnnotation($entityClass, Table::class);
        $context->setEntityClass($entityClass->getName());
        $context->setAnnotationValue($annotation ? $annotation->name : null);

        return $this->namingStrategy->toTableName($context);
    }

    private function getColumns(\ReflectionClass $entityClass): array
    {
        foreach ($entityClass->getProperties() as $property) {
        }
    }
}
