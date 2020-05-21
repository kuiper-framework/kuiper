<?php

declare(strict_types=1);

namespace kuiper\db\annotation;

/**
 * @Annotation
 * @Target({"PROPERTY","ANNOTATION"})
 */
final class Column implements Annotation
{
    /**
     * @var string
     */
    public $name;

    /**
     * @var mixed
     */
    public $type = 'string';

    /**
     * @var int
     */
    public $length;

    /**
     * The precision for a decimal (exact numeric) column (Applies only for decimal column).
     *
     * @var int
     */
    public $precision = 0;

    /**
     * The scale for a decimal (exact numeric) column (Applies only for decimal column).
     *
     * @var int
     */
    public $scale = 0;

    /**
     * @var bool
     */
    public $unique = false;

    /**
     * @var bool
     */
    public $nullable = false;

    /**
     * @var array
     */
    public $options = [];
}
