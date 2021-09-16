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
use kuiper\helper\PropertyResolverInterface;
use Psr\Container\ContainerInterface;

/**
 * @Annotation
 * @Target({"CLASS", "METHOD", "ANNOTATION"})
 */
class ConditionalOnProperty implements Condition
{
    /**
     * @var string
     */
    public $value;

    /**
     * @var mixed
     */
    public $hasValue;

    /**
     * @var bool
     */
    public $matchIfMissing = false;

    public function matches(ContainerInterface $container): bool
    {
        if (!$container->has(PropertyResolverInterface::class)) {
            throw new \InvalidArgumentException(PropertyResolverInterface::class.' should be registered in container');
        }
        $value = $container->get(PropertyResolverInterface::class)->get($this->value);
        if (isset($value)) {
            if (isset($this->hasValue)) {
                return $this->equals($value, $this->hasValue);
            }

            return true;
        }

        return $this->matchIfMissing;
    }

    /**
     * @param mixed             $value
     * @param bool|string|mixed $hasValue
     */
    private function equals($value, $hasValue): bool
    {
        if (is_bool($hasValue)) {
            return ((bool) $value) === $hasValue;
        }

        return ((string) $value) === ((string) $hasValue);
    }
}
