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

use kuiper\serializer\Serializer;
use kuiper\swoole\config\FoundationConfiguration;
use kuiper\swoole\config\ServerConfiguration;
use kuiper\tars\server\ClientProperties;
use kuiper\tars\server\Config;
use kuiper\tars\server\ServerProperties;
use kuiper\tars\server\TarsTcpReceiveEventListener;
use kuiper\tars\TestCase;

class TarsServerConfigurationTest extends TestCase
{
    public function testServerProperties()
    {
        $normalizer = new Serializer();
        $config = Config::parseFile(dirname(__DIR__).'/fixtures/PHPTest.PHPHttpServer.config.conf');
        $server = $config->get('application.tars.server');
        /** @var ServerProperties $serverProperties */
        $serverProperties = $normalizer->denormalize($server, ServerProperties::class);
        // var_export([$server, $serverProperties]);
        $this->assertNotEmpty($serverProperties->getAdapters());

        /** @var ClientProperties $clientProperties */
        $clientProperties = $normalizer->denormalize($config->get('application.tars.client'), ClientProperties::class);
        // var_export($clientProperties);
        $this->assertNotNull($clientProperties->getLocator());
    }

    protected function getConfigurations(): array
    {
        return [
            new FoundationConfiguration(),
            new ServerConfiguration(),
            new TarsClientConfiguration(),
            new TarsServerConfiguration(),
        ];
    }

    protected function getConfig(): array
    {
        return [
            'application' => [
                'server' => [
                    'ports' => [
                        3000 => [
                            'protocol' => 'tcp',
                            'listener' => TarsTcpReceiveEventListener::class,
                        ],
                    ],
                ],
            ],
        ];
    }
}
