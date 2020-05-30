<?php

declare(strict_types=1);

namespace kuiper\db\metadata;

use kuiper\annotations\AnnotationReader;
use kuiper\annotations\AnnotationReaderInterface;
use kuiper\db\annotation\Annotation;
use kuiper\db\annotation\Column as ColumnAnnotation;
use kuiper\db\annotation\Convert;
use kuiper\db\annotation\Embeddable;
use kuiper\db\annotation\Entity;
use kuiper\db\annotation\Enumerated;
use kuiper\db\annotation\Table;
use kuiper\db\annotation\Transient;
use kuiper\db\converter\AttributeConverterInterface;
use kuiper\db\converter\AttributeConverterRegistry;
use kuiper\db\converter\EnumConverter;
use kuiper\db\exception\MetaModelException;
use kuiper\reflection\FqcnResolver;
use kuiper\reflection\ReflectionFileFactoryInterface;
use kuiper\reflection\ReflectionType;
use kuiper\reflection\ReflectionTypeInterface;
use kuiper\reflection\type\ClassType;
use kuiper\reflection\type\MixedType;

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
    /**
     * @var AttributeConverterRegistry
     */
    private $attributeConverterRegistry;
    /**
     * @var ReflectionFileFactoryInterface|null
     */
    private $reflectionFileFactory;

    public function __construct(AttributeConverterRegistry $attributeConverterRegistry,
                                NamingStrategyInterface $namingStrategy,
                                ?AnnotationReaderInterface $annotationReader,
                                ?ReflectionFileFactoryInterface $reflectionFileFactory)
    {
        $this->namingStrategy = $namingStrategy;
        $this->attributeConverterRegistry = $attributeConverterRegistry;
        $this->annotationReader = $annotationReader ?: AnnotationReader::getInstance();
        $this->reflectionFileFactory = $reflectionFileFactory;
    }

    /**
     * {@inheritdoc}
     */
    public function create($repository): MetaModelInterface
    {
        $reflectionClass = new \ReflectionClass($repository);
        $entityClass = $this->getEntityClass($reflectionClass);

        return new MetaModel($this->getTableName($entityClass), $entityClass->getName(),
            $this->getProperties($entityClass, null));
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

    private function getTableName(\ReflectionClass $entityClass): string
    {
        $context = new NamingContext();
        /** @var Table $annotation */
        $annotation = $this->annotationReader->getClassAnnotation($entityClass, Table::class);
        $context->setEntityClass($entityClass->getName());
        $context->setAnnotationValue($annotation ? $annotation->name : null);

        return $this->namingStrategy->toTableName($context);
    }

    private function getProperties(\ReflectionClass $modelClass, ?MetaModelProperty $parent): array
    {
        $metaProperties = [];
        foreach ($modelClass->getProperties() as $property) {
            if ($property->isStatic()) {
                continue;
            }
            $annotations = $this->annotationReader->getPropertyAnnotations($property);
            if ($annotations && $this->hasAnnotation($annotations, Transient::class)) {
                continue;
            }
            $metaProperties[] = $this->createProperty($property, $annotations, $parent);
        }

        return $metaProperties;
    }

    private function getAnnotation(array $annotations, string $annotationName): ?Annotation
    {
        foreach ($annotations as $annotation) {
            if ($annotation instanceof $annotationName) {
                return $annotation;
            }
        }

        return null;
    }

    private function hasAnnotation(array $annotations, string $annotationName): bool
    {
        return null !== $this->getAnnotation($annotations, $annotationName);
    }

    private function getPropertyType(\ReflectionProperty $property): ReflectionTypeInterface
    {
        $docComment = $property->getDocComment();
        if (!$docComment || !preg_match('/@var\s+(\S+)/', $docComment, $matches)) {
            return new MixedType();
        }
        $typeName = $matches[1];
        if (empty($typeName)) {
            throw new \InvalidArgumentException('Type cannot be empty');
        }
        $type = ReflectionType::parse($typeName);
        if ($type->isClass()) {
            $file = $property->getDeclaringClass()->getFileName();
            $fqcnResolver = new FqcnResolver($this->reflectionFileFactory->create($file));

            return new ClassType($fqcnResolver->resolve($type->getName(), $property->getDeclaringClass()->getNamespaceName()));
        }

        return $type;
    }

    private function createProperty(\ReflectionProperty $property, $annotations, ?MetaModelProperty $parent): MetaModelProperty
    {
        $type = $this->getPropertyType($property);
        $metaProperty = new MetaModelProperty($property, $type, $parent, $annotations);
        $attributeConverter = $this->getAttributeConverter($metaProperty);
        if ($attributeConverter) {
            /** @var ColumnAnnotation $columnAnnotation */
            $columnAnnotation = $this->getAnnotation($annotations, ColumnAnnotation::class);
            $namingContext = new NamingContext();
            $namingContext->setEntityClass($metaProperty->getEntityClass());
            $namingContext->setAnnotationValue($columnAnnotation ? $columnAnnotation->name : null);
            $namingContext->setPropertyName($property->getName());
            $columnName = $this->namingStrategy->toColumnName($namingContext);

            $metaProperty->createColumn($columnName, $attributeConverter);
        }
        if (!$type->isClass()) {
            throw new MetaModelException(sprintf('Unsupported type %s for %s property %s', $type->getName(), $metaProperty->getEntityClass(), $metaProperty->getPath()));
        }
        $reflectionClass = new \ReflectionClass($type->getName());
        $isEmbeddable = $this->annotationReader->getClassAnnotation($reflectionClass, Embeddable::class);
        if (!$isEmbeddable) {
            throw new MetaModelException(sprintf('%s property %s type class %s is not annotated with %s', $metaProperty->getEntityClass(), $metaProperty->getPath(), $type->getName(), Embeddable::class));
        }

        $metaProperty->setChildren($this->getProperties($reflectionClass, $metaProperty));

        return $metaProperty;
    }

    private function getAttributeConverter(MetaModelProperty $metaProperty): ?AttributeConverterInterface
    {
        /** @var Convert $annotation */
        $annotation = $metaProperty->getAnnotation(Convert::class);
        if ($annotation) {
            return $this->attributeConverterRegistry->get($annotation->value);
        }
        /** @var Enumerated $enumerated */
        $enumerated = $metaProperty->getAnnotation(Enumerated::class);
        if ($enumerated) {
            return new EnumConverter(Enumerated::ORDINAL === $enumerated->value);
        }

        return $this->attributeConverterRegistry->get($metaProperty->getType()->getName());
    }
}
