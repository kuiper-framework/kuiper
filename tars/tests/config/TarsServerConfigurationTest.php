<?php

declare(strict_types=1);

namespace kuiper\tars\config;

use kuiper\serializer\Serializer;
use kuiper\tars\server\ClientProperties;
use kuiper\tars\server\Config;
use kuiper\tars\server\ServerProperties;
use PHPUnit\Framework\TestCase;

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
}
