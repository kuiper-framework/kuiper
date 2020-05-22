<?php

declare(strict_types=1);

namespace kuiper\db\annotation;

use Doctrine\Common\Annotations\Annotation\Required;

/**
 * @Annotation
 * @Target("CLASS")
 */
final class Entity implements Annotation
{
    /**
     * @var string
     * @Required()
     */
    public $value;
}
