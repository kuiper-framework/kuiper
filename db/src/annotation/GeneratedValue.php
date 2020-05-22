<?php

declare(strict_types=1);

namespace kuiper\db\annotation;

/**
 * @Annotation
 * @Target("PROPERTY")
 */
final class GeneratedValue implements Annotation
{
    /**
     * The type of Id generator.
     *
     * @var string
     */
    public $value = 'AUTO';
}
