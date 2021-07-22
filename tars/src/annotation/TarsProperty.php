<?php

declare(strict_types=1);

namespace kuiper\tars\annotation;

/**
 * @Annotation
 * @Target({"PROPERTY"})
 */
final class TarsProperty
{
    /**
     * @Required()
     *
     * @var string
     */
    public $type;

    /**
     * @Required()
     *
     * @var int
     */
    public $order;

    /**
     * @var bool
     */
    public $required;
}
