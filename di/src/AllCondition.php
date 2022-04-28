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

namespace kuiper\di;

use Psr\Container\ContainerInterface;
use ReflectionAttribute;

class AllCondition implements Condition
{
    /**
     * @var Condition[]
     */
    private array $conditions;

    public function __construct(Condition ...$conditions)
    {
        foreach ($conditions as $condition) {
            $this->addCondition($condition);
        }
    }

    public function addCondition(Condition $condition): void
    {
        $this->conditions[] = $condition;
    }

    public function matches(ContainerInterface $container): bool
    {
        foreach ($this->conditions as $condition) {
            if (!$condition->matches($container)) {
                return false;
            }
        }

        return true;
    }

    public static function create(\Reflector $reflector): ?AllCondition
    {
        if ($reflector instanceof \ReflectionClass || $reflector instanceof \ReflectionMethod) {
            $attributes = $reflector->getAttributes(Condition::class, ReflectionAttribute::IS_INSTANCEOF);
        } else {
            throw new \InvalidArgumentException('invalid reflector '.get_class($reflector));
        }

        return empty($attributes) ? null : new AllCondition(...array_map(static function (ReflectionAttribute $attribute) {
            return $attribute->newInstance();
        }, $attributes));
    }
}
