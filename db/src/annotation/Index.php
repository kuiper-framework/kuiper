<?php

declare(strict_types=1);

namespace kuiper\db\annotation;

/**
 * @Annotation
 * @Target("ANNOTATION")
 */
final class Index implements Annotation
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
     * @var string[]
     */
    public $flags;

    /**
     * @var array
     */
    public $options;
}
