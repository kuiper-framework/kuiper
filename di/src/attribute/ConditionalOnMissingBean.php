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
class ConditionalOnMissingBean implements Condition
{
    public function __construct(private readonly string $bean)
    {
    }

    public function matches(ContainerInterface $container): bool
    {
        return !$container->has($this->bean);
    }
}
