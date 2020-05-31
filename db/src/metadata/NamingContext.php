<?php

declare(strict_types=1);

namespace kuiper\db\metadata;

class NamingContext
{
    /**
     * @var \ReflectionClass
     */
    private $entityClass;

    /**
     * @var string
     */
    private $annotationValue;

    /**
     * @var string
     */
    private $propertyName;

    public function getEntityClass(): \ReflectionClass
    {
        return $this->entityClass;
    }

    public function setEntityClass(\ReflectionClass $entityClass): void
    {
        $this->entityClass = $entityClass;
    }

    public function getEntityClassShortName(): string
    {
        return $this->entityClass->getShortName();
    }

    /**
     * @return string
     */
    public function getAnnotationValue(): ?string
    {
        return $this->annotationValue;
    }

    public function setAnnotationValue(string $annotationValue): void
    {
        $this->annotationValue = $annotationValue;
    }

    /**
     * @return string
     */
    public function getPropertyName(): ?string
    {
        return $this->propertyName;
    }

    public function setPropertyName(string $propertyName): void
    {
        $this->propertyName = $propertyName;
    }
}
