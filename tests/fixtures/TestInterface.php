<?php
namespace kuiper\annotations\fixtures;

use kuiper\annotations\fixtures\annotation\Secure;

interface TestInterface
{
    /**
     * @Secure
     */
    public function foo();
}
