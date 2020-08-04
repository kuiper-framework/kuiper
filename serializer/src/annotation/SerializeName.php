<?php

declare(strict_types=1);

namespace kuiper\serializer\annotation;

/**
 * changes serialize field name.
 *
 * @Annotation
 * @Target({"METHOD", "PROPERTY"})
 */
class SerializeName
{
    /**
     * @var string
     * @Required
     */
    public $value;
}
