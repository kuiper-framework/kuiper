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

namespace kuiper\di\attribute;

use Attribute;
use kuiper\di\Condition;
use Psr\Container\ContainerInterface;

#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_METHOD)]
class ConditionalOnMissingClass implements Condition
{
    public function __construct(private string $className)
    {
    }

    public function matches(ContainerInterface $container): bool
    {
        return !class_exists($this->className) && !interface_exists($this->className);
    }
}
