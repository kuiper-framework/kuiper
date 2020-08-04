<?php

declare(strict_types=1);

namespace kuiper\serializer\annotation;

/**
 * Mark field not serialize.
 *
 * @Annotation
 * @Target({"METHOD", "PROPERTY"})
 */
class SerializeIgnore
{
}
