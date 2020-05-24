<?php

declare(strict_types=1);

namespace kuiper\db\metadata;

class NamingContext
{
    /**
     * @var string
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

    public function getEntityClass(): string
    {
        return $this->entityClass;
    }

    public function setEntityClass(string $entityClass): void
    {
        $this->entityClass = $entityClass;
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
