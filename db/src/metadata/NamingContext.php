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

class NamingContext
{
    private ?\ReflectionClass $entityClass = null;

    private ?string $annotationValue = null;

    private ?string $propertyName = null;

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
