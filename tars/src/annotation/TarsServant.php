<?php

declare(strict_types=1);

namespace kuiper\tars\annotation;

use kuiper\di\annotation\Service;

/**
 * @Annotation
 * @Target({"CLASS"})
 */
class TarsServant extends Service
{
    /**
     * @Required
     *
     * @var string
     */
    public $service;
}
