<?php

declare(strict_types=1);

namespace kuiper\di\annotation;

use kuiper\di\Condition;
use Psr\Container\ContainerInterface;

/**
 * @Annotation
 * @Target({"CLASS", "METHOD", "ANNOTATION"})
 */
class Conditional implements Condition
{
    /**
     * @var string
     */
    public $value;

    public function matches(ContainerInterface $container): bool
    {
        return $container->get($this->value)->matches();
    }
}
