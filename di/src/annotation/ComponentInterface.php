<?php

declare(strict_types=1);

namespace kuiper\di\annotation;

use kuiper\annotations\AnnotationHandlerInterface;

interface ComponentInterface extends AnnotationHandlerInterface
{
    /**
     * Sets the components bean name.
     */
    public function setComponentId(string $name): void;

    /**
     * @return string
     */
    public function getComponentId(): string;
}
