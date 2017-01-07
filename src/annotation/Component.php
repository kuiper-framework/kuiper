<?php

namespace kuiper\di\annotation;

/**
 * "Component" annotation.
 *
 * Marks a class as component
 *
 * @Annotation
 * @Target({"CLASS"})
 */
final class Component
{
    /**
     * @var string
     */
    public $name;
}
