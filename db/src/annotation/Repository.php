<?php

declare(strict_types=1);

namespace kuiper\db\annotation;

use Doctrine\Common\Annotations\Annotation\Required;
use kuiper\di\annotation\Service;

/**
 * @Annotation
 * @Target({"CLASS"})
 */
class Repository extends Service
{
    /**
     * @var string
     * @Required()
     */
    public $entityClass;
}
