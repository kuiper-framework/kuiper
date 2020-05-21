<?php

declare(strict_types=1);

namespace kuiper\db\annotation;

/**
 * @Annotation
 * @Target("ANNOTATION")
 */
final class UniqueConstraint implements Annotation
{
    /**
     * @var string
     */
    public $name;

    /**
     * @var string[]
     */
    public $columns;

    /**
     * @var array
     */
    public $options;
}
