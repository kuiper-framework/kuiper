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

use kuiper\annotations\AnnotationReader;
use kuiper\annotations\AnnotationReaderInterface;
use kuiper\db\annotation\Annotation;
use kuiper\db\annotation\Column as ColumnAnnotation;
use kuiper\db\annotation\Convert;
use kuiper\db\annotation\Embeddable;
use kuiper\db\annotation\Enumerated;
use kuiper\db\annotation\Repository;
use kuiper\db\annotation\Table;
use kuiper\db\annotation\Transient;
use kuiper\db\converter\AttributeConverterInterface;
use kuiper\db\converter\AttributeConverterRegistry;
use kuiper\db\converter\EnumConverter;
use kuiper\db\exception\MetaModelException;
use kuiper\helper\Text;
use kuiper\reflection\FqcnResolver;
use kuiper\reflection\ReflectionFileFactory;
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

    /**
     * @var MetaModelInterface[]
     */
    private $cache;

    public function __construct(AttributeConverterRegistry $attributeConverterRegistry,
                                ?NamingStrategyInterface $namingStrategy,
                                ?AnnotationReaderInterface $annotationReader,
                                ?ReflectionFileFactoryInterface $reflectionFileFactory)
    {
        $this->attributeConverterRegistry = $attributeConverterRegistry;
        $this->namingStrategy = $namingStrategy ?? new NamingStrategy();
        $this->annotationReader = $annotationReader ?? AnnotationReader::getInstance();
        $this->reflectionFileFactory = $reflectionFileFactory ?? ReflectionFileFactory::getInstance();
    }

    /**
     * {@inheritdoc}
     */
    public function create(string $entityClass): MetaModelInterface
    {
        return $this->createInterval(new \ReflectionClass($entityClass));
    }

    /**
     * {@inheritdoc}
     */
    public function createFromRepository(string $repositoryClass): MetaModelInterface
    {
        return $this->createInterval($this->getEntityClass(new \ReflectionClass($repositoryClass)));
    }

    private function createInterval(\ReflectionClass $entityClass): MetaModelInterface
    {
        if (isset($this->cache[$entityClass->getName()])) {
            return $this->cache[$entityClass->getName()];
        }

        return $this->cache[$entityClass->getName()] = new MetaModel(
            $this->getTableName($entityClass), $entityClass, $this->getProperties($entityClass, null));
    }

    private function getEntityClass(\ReflectionClass $reflectionClass): \ReflectionClass
    {
        /** @var Repository|null $annotation */
        $annotation = $this->annotationReader->getClassAnnotation($reflectionClass, Repository::class);
        if (null === $annotation) {
            foreach ($reflectionClass->getInterfaces() as $interface) {
                $annotation = $this->annotationReader->getClassAnnotation($interface, Repository::class);
                if (null !== $annotation) {
                    break;
                }
            }
        }
        if (null === $annotation) {
            throw new \InvalidArgumentException($reflectionClass->getName().' should annotation with @'.Repository::class);
        }

        return new \ReflectionClass($annotation->entityClass);
    }

    private function getTableName(\ReflectionClass $entityClass): string
    {
        $context = new NamingContext();
        /** @var Table|null $annotation */
        $annotation = $this->annotationReader->getClassAnnotation($entityClass, Table::class);
        $context->setEntityClass($entityClass);
        if (null !== $annotation && Text::isNotEmpty($annotation->name)) {
            $context->setAnnotationValue($annotation->name);
        }

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
            if (!empty($annotations) && $this->hasAnnotation($annotations, Transient::class)) {
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
        $typeName = str_replace('|null', '', $matches[1]);
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

    /**
     * @param ColumnAnnotation[] $annotations
     *
     * @throws MetaModelException
     * @throws \ReflectionException
     */
    private function createProperty(\ReflectionProperty $property, array $annotations, ?MetaModelProperty $parent): MetaModelProperty
    {
        $type = $this->getPropertyType($property);
        $metaProperty = new MetaModelProperty($property, $type, $parent, $annotations);
        $attributeConverter = $this->getAttributeConverter($metaProperty);
        if (null !== $attributeConverter) {
            /** @var ColumnAnnotation|null $columnAnnotation */
            $columnAnnotation = $this->getAnnotation($annotations, ColumnAnnotation::class);
            $namingContext = new NamingContext();
            $namingContext->setEntityClass($metaProperty->getEntityClass());
            if (null !== $columnAnnotation && Text::isNotEmpty($columnAnnotation->name)) {
                $namingContext->setAnnotationValue($columnAnnotation->name);
            }
            $namingContext->setPropertyName($property->getName());
            $columnName = $this->namingStrategy->toColumnName($namingContext);

            $metaProperty->createColumn($columnName, $attributeConverter);
        } else {
            if (!$type->isClass()) {
                throw new MetaModelException(sprintf('Unsupported type %s for %s property %s', $type->getName(), $metaProperty->getEntityClass()->getName(), $metaProperty->getPath()));
            }
            $reflectionClass = new \ReflectionClass($type->getName());
            /** @var Embeddable|null $isEmbeddable */
            $isEmbeddable = $this->annotationReader->getClassAnnotation($reflectionClass, Embeddable::class);
            if (null === $isEmbeddable) {
                throw new MetaModelException(sprintf('%s property %s type class %s is not annotated with %s', $metaProperty->getEntityClass()->getName(), $metaProperty->getPath(), $type->getName(), Embeddable::class));
            }

            $metaProperty->setChildren($this->getProperties($reflectionClass, $metaProperty));
        }

        return $metaProperty;
    }

    private function getAttributeConverter(MetaModelProperty $metaProperty): ?AttributeConverterInterface
    {
        /** @var Convert|null $annotation */
        $annotation = $metaProperty->getAnnotation(Convert::class);
        if (null !== $annotation) {
            return $this->attributeConverterRegistry->get($annotation->value);
        }
        /** @var Enumerated|null $enumerated */
        $enumerated = $metaProperty->getAnnotation(Enumerated::class);
        if (null !== $enumerated) {
            return new EnumConverter(Enumerated::ORDINAL === $enumerated->value);
        }

        return $this->attributeConverterRegistry->get($metaProperty->getType()->getName());
    }
}
