<?php

declare(strict_types=1);

namespace kuiper\db\annotation;

use Doctrine\Common\Annotations\Annotation\Enum;

/**
 * @Annotation
 * @Target("PROPERTY")
 */
final class Enumerated implements Annotation
{
    public const ORDINAL = 'ORDINAL';
    public const STRING = 'STRING';

    /**
     * @Enum({"ORDINAL", "STRING"})
     *
     * @var string
     */
    public $value = self::ORDINAL;
}
