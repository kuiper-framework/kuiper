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
use kuiper\helper\PropertyResolverInterface;
use Psr\Container\ContainerInterface;

#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_METHOD)]
class ConditionalOnProperty implements Condition
{
    public function __construct(
        private readonly string $name,
        private readonly mixed $hasValue = null,
        private readonly bool $matchIfMissing = false)
    {
    }

    public function matches(ContainerInterface $container): bool
    {
        if (!$container->has(PropertyResolverInterface::class)) {
            throw new \InvalidArgumentException(PropertyResolverInterface::class.' should be registered in container');
        }
        $value = $container->get(PropertyResolverInterface::class)->get($this->name);
        if (isset($value)) {
            if (isset($this->hasValue)) {
                return $this->equals($value, $this->hasValue);
            }

            return true;
        }

        return $this->matchIfMissing;
    }

    private function equals(mixed $value, mixed $hasValue): bool
    {
        if (is_bool($hasValue)) {
            return ((bool) $value) === $hasValue;
        }

        return ((string) $value) === ((string) $hasValue);
    }
}
