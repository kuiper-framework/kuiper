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

namespace kuiper\tars\config;

use kuiper\tars\integration\QueryFServant;
use kuiper\tars\TestCase;

class TarsClientConfigurationTest extends TestCase
{
    public function testCreateClient()
    {
        $proxy = $this->getContainer()->get(QueryFServant::class);
        $this->assertInstanceOf(QueryFServant::class, $proxy);
        // $ret = $proxy->findObjectById('winwin.option.OptionObj');
        // print_r($ret);
    }

    protected function getConfig(): array
    {
        return [
            'application' => [
                'tars' => [
                    'client' => [
                        'options' => [
                            QueryFServant::class => [
                                'endpoint' => 'tars.tarsregistry.QueryObj@tcp -h 127.0.0.1 -p 17890',
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }

    protected function getConfigurations(): array
    {
        return [
            new TarsClientConfiguration(),
        ];
    }
}
