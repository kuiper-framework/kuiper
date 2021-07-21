<?php

declare(strict_types=1);

namespace kuiper\reflection;

class ReflectionPropertyDocBlock implements ReflectionPropertyDocBlockInterface
{
    /**
     * \ReflectionProperty.
     */
    private $property;

    /**
     * @var ReflectionTypeInterface
     */
    private $type;

    /**
     * ReflectionPropertyDocBlockImpl constructor.
     */
    public function __construct(\ReflectionProperty $property, ReflectionTypeInterface $type)
    {
        $this->property = $property;
        $this->type = $type;
    }

    public function getProperty(): \ReflectionProperty
    {
        return $this->property;
    }

    /**
     * {@inheritDoc}
     */
    public function getType(): ReflectionTypeInterface
    {
        return $this->type;
    }
}
