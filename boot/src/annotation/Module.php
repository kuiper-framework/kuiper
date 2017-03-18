<?php

namespace kuiper\boot\annotation;

/**
 * @Annotation
 * @Target({"CLASS"})
 */
final class Module
{
    /**
     * @var string
     */
    public $name;

    /**
     * @var string
     */
    public $namespace;

    /**
     * @var string
     */
    public $basePath;
}
