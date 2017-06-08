<?php

namespace kuiper\boot\fixtures\app2;

use kuiper\boot\annotation\Module;
use kuiper\boot\Provider;

/**
 * @Module("app2")
 */
class ServiceProvider extends Provider
{
    public function register()
    {
        $this->services->addDefinitions([
            'foo' => 'app2_foo',
        ]);
    }
}
