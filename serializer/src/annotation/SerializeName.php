<?php

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
