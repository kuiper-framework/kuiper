<?php

declare(strict_types=1);

namespace kuiper\di\annotation;

trait ComponentTrait
{
    /**
     * @var \ReflectionClass
     */
    protected $class;

    public function setTarget($class): void
    {
        $this->class = $class;
    }
}
