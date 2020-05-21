<?php

declare(strict_types=1);

namespace kuiper\db\annotation;

/**
 * @Annotation
 * @Target("CLASS")
 */
final class Table implements Annotation
{
    /**
     * @var string
     */
    public $name;

    /**
     * @var string
     */
    public $prefix;

    /**
     * @var Index[]
     */
    public $indexes;

    /**
     * @var UniqueConstraint[]
     */
    public $uniqueConstraints;

    /**
     * @var string[]
     */
    public $shardBy;

    /**
     * @var array
     */
    public $options = [];
}
