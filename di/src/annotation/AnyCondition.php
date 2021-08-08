<?php

declare(strict_types=1);

namespace kuiper\di\annotation;

use kuiper\di\Condition;
use Psr\Container\ContainerInterface;

/**
 * @Annotation
 * @Target({"CLASS", "METHOD", "ANNOTATION"})
 *
 * @property Condition[] $value
 */
class AnyCondition implements Condition
{
    /**
     * @var array
     */
    public $value;

    public function matches(ContainerInterface $container): bool
    {
        foreach ($this->value as $condition) {
            if ($condition->matches($container)) {
                return true;
            }
        }

        return false;
    }
}
