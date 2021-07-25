<?php

declare(strict_types=1);

namespace kuiper\tars\config;

use kuiper\tars\integration\QueryFServant;
use PHPUnit\Framework\TestCase;

class TarsClientConfigurationTest extends TestCase
{
    public function testCreateClient()
    {
        $config = new TarsClientConfiguration();
        /** @var QueryFServant $proxy */
        $proxy = $config->createClient(QueryFServant::class, [
            'endpoint' => 'tcp://127.0.0.1:17890',
        ]);
        $ret = $proxy->findObjectById('winwin.option.OptionObj');
        print_r($ret);
    }
}
