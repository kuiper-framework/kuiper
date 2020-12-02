<?php

declare(strict_types=1);

namespace kuiper\db\annotation;

/**
 * @Annotation
 * @Target("PROPERTY")
 */
final class NaturalId implements Annotation
{
    /**
     * unique index name.
     *
     * @var string
     */
    public $value;
}
