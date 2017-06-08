<?php

namespace kuiper\boot\fixtures\app1;

use kuiper\boot\annotation\Module;
use kuiper\boot\Provider;

/**
 * @Module
 */
class ServiceProvider extends Provider
{
    public function register()
    {
        $this->services->addDefinitions([
            'foo' => 'app1_foo',
        ]);
    }
}
