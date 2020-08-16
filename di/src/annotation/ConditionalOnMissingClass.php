<?php

declare(strict_types=1);

namespace kuiper\di\annotation;

use Psr\Container\ContainerInterface;

/**
 * @Annotation
 * @Target({"CLASS", "METHOD"})
 */
class ConditionalOnMissingClass implements Conditional
{
    /**
     * @var string
     */
    public $value;

    public function match(ContainerInterface $container): bool
    {
        return !class_exists($this->value) && !interface_exists($this->value);
    }
}
