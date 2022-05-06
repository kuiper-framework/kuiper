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

use kuiper\db\attribute\Attribute;
use kuiper\db\attribute\Column as ColumnAttribute;
use kuiper\db\attribute\Convert;
use kuiper\db\attribute\Embeddable;
use kuiper\db\attribute\Enumerated;
use kuiper\db\attribute\Repository;
use kuiper\db\attribute\Table;
use kuiper\db\attribute\Transient;
use kuiper\db\converter\AttributeConverterInterface;
use kuiper\db\converter\AttributeConverterRegistry;
use kuiper\db\converter\EnumConverter;
use kuiper\db\exception\MetaModelException;
use kuiper\reflection\ReflectionDocBlockFactoryInterface;

class MetaModelFactory implements MetaModelFactoryInterface
{

    /**
     * @var MetaModelInterface[]
     */
    private array $cache = [];

    public function __construct(
        private readonly AttributeConverterRegistry $attributeConverterRegistry,
        private readonly NamingStrategyInterface $namingStrategy,
        private readonly ReflectionDocBlockFactoryInterface $reflectionDocBlockFactory)
    {
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
        $attributes = $reflectionClass->getAttributes(Repository::class);
        if (count($attributes) === 0) {
            foreach ($reflectionClass->getInterfaces() as $interface) {
                $attributes = $interface->getAttributes(Repository::class);
                if (count($attributes) > 0) {
                    break;
                }
            }
        }
        if (count($attributes) === 0) {
            throw new \InvalidArgumentException($reflectionClass->getName() . ' should annotation with @' . Repository::class);
        }

        /** @var Repository $attribute */
        $attribute = $attributes[0]->newInstance();
        return new \ReflectionClass($attribute->getEntityClass());
    }

    private function getTableName(\ReflectionClass $entityClass): string
    {
        $context = new NamingContext();
        $context->setEntityClass($entityClass);

        $attributes = $entityClass->getAttributes(Table::class);
        if (count($attributes) > 0) {
            /** @var Table $attribute */
            $attribute = $attributes[0]->newInstance();
            $context->setAnnotationValue($attribute->getName());
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
            $attributes = $property->getAttributes(Transient::class);
            if (count($attributes) > 0) {
                continue;
            }
            $attributes = array_map(static function (\ReflectionAttribute $attribute) {
                return $attribute->newInstance();
            }, $property->getAttributes(Attribute::class, \ReflectionAttribute::IS_INSTANCEOF));
            $metaProperties[] = $this->createProperty($property, $attributes, $parent);
        }

        return $metaProperties;
    }

    /**
     * @param Attribute[] $attributes
     *
     * @throws MetaModelException
     * @throws \ReflectionException
     */
    private function createProperty(\ReflectionProperty $property, array $attributes, ?MetaModelProperty $parent): MetaModelProperty
    {
        $type = $this->reflectionDocBlockFactory->createPropertyDocBlock($property)->getType();
        $metaProperty = new MetaModelProperty($property, $type, $parent, $attributes);
        $attributeConverter = $this->getAttributeConverter($metaProperty);
        if (null !== $attributeConverter) {
            $namingContext = new NamingContext();
            $namingContext->setEntityClass($metaProperty->getEntityClass());

            $attributes = $property->getAttributes(ColumnAttribute::class);
            if (count($attributes) > 0) {
                /** @var ColumnAttribute $attribute */
                $attribute = $attributes[0]->newInstance();
                $namingContext->setAnnotationValue($attribute->getName());
            }
            $namingContext->setPropertyName($property->getName());
            $columnName = $this->namingStrategy->toColumnName($namingContext);
            $metaProperty->createColumn($columnName, $attributeConverter);
        } else {
            if (!$type->isClass()) {
                throw new MetaModelException(sprintf('Unsupported type %s for %s property %s',
                    $type->getName(), $metaProperty->getEntityClass()->getName(), $metaProperty->getPath()));
            }
            $reflectionClass = new \ReflectionClass($type->getName());
            if (count($reflectionClass->getAttributes(Embeddable::class)) === 0) {
                throw new MetaModelException(sprintf('%s property %s type class %s is not annotated with %s',
                    $metaProperty->getEntityClass()->getName(), $metaProperty->getPath(), $type->getName(), Embeddable::class));
            }

            $metaProperty->setChildren($this->getProperties($reflectionClass, $metaProperty));
        }

        return $metaProperty;
    }

    private function getAttributeConverter(MetaModelProperty $metaProperty): ?AttributeConverterInterface
    {
        $converter = $metaProperty->getAttribute(Convert::class);
        if (null !== $converter) {
            return $this->attributeConverterRegistry->get($converter->getConverter());
        }
        if ($metaProperty->hasAttribute(Enumerated::class)) {
            return new EnumConverter();
        }

        return $this->attributeConverterRegistry->get($metaProperty->getType()->getName());
    }
}
