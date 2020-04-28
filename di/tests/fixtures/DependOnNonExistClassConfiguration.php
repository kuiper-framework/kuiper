<?php

declare(strict_types=1);

namespace kuiper\di\fixtures;

use kuiper\di\annotation\Bean;
use kuiper\di\annotation\ConditionalOnClass;

/**
 * @ConditionalOnClass(NotExistClass::class)
 */
class DependOnNonExistClassConfiguration
{
    /**
     * @Bean("foo")
     */
    public function foo()
    {
        return ['foo' => 'nonExistClass'];
    }
}
