<?php

/*
 * This file is part of the Kuiper package.
 *
 * (c) Ye Wenbin <wenbinye@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

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
class NoneCondition implements Condition
{
    /**
     * @var array
     */
    public $value;

    public function matches(ContainerInterface $container): bool
    {
        foreach ($this->value as $condition) {
            if ($condition->matches($container)) {
                return false;
            }
        }

        return true;
    }
}
