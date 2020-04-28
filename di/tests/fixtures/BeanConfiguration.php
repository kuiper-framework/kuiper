<?php

declare(strict_types=1);

namespace kuiper\di\fixtures;

use DI\Annotation\Inject;
use kuiper\di\annotation\Bean;

class BeanConfiguration
{
    /**
     * @Bean()
     */
    public function bar(): Bar
    {
        return new Bar('bar');
    }

    /**
     * @Bean(name="otherBar")
     */
    public function otherBar(): Bar
    {
        return new Bar('other');
    }

    /**
     * @Bean("foo")
     * @Inject({"bar"="otherBar"})
     */
    public function foo(Bar $bar): Bar
    {
        return $bar;
    }
}
