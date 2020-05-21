<?php

declare(strict_types=1);

namespace kuiper\db\annotation;

/**
 * @Annotation
 * @Target("PROPERTY")
 */
final class Convert implements Annotation
{
    /**
     * The name of converter.
     *
     * @var string
     * @Required
     */
    public $value;
}
