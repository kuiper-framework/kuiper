<?php

declare(strict_types=1);

namespace kuiper\db\annotation;

/**
 * @Annotation
 * @Target("PROPERTY")
 */
final class GeneratedValue implements Annotation
{
    const AUTO = 'AUTO';

    /**
     * The type of Id generator.
     *
     * @var string
     *
     * @Enum
     */
    public $strategy = 'AUTO';
}
