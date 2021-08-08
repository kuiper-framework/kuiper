<?php

declare(strict_types=1);

namespace kuiper\di\annotation;

use kuiper\di\Condition;
use Psr\Container\ContainerInterface;

/**
 * @Annotation
 * @Target({"CLASS", "METHOD", "ANNOTATION"})
 */
class ConditionalOnBean implements Condition
{
    /**
     * @var string
     */
    public $value;

    public function matches(ContainerInterface $container): bool
    {
        return $container->has($this->value);
    }
}
