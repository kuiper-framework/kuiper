<?php

declare(strict_types=1);

namespace kuiper\di\annotation;

use Doctrine\Common\Annotations\Annotation\Required;

/**
 * @Annotation
 * @Target({"CLASS"})
 */
class Command implements ComponentInterface
{
    use ComponentTrait;

    /**
     * @var string
     * @Required
     */
    public $name;
}
