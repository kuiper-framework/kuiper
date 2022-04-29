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
class NoneCondition implements Condition
{
    /**
     * @var Condition[]
     */
    private array $conditions;

    public function __construct(Condition ...$conditions)
    {
        $this->conditions = $conditions;
    }

    public function matches(ContainerInterface $container): bool
    {
        foreach ($this->conditions as $condition) {
            if ($condition->matches($container)) {
                return false;
            }
        }

        return true;
    }
}
