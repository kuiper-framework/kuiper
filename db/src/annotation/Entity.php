<?php

declare(strict_types=1);

namespace kuiper\db\annotation;

/**
 * @Annotation
 * @Target("CLASS")
 */
final class Entity implements Annotation
{
    /**
     * @var string
     */
    public $repositoryClass;

    /**
     * @var bool
     */
    public $readOnly = false;
}
