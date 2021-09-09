<?php

declare(strict_types=1);

namespace kuiper\tars\server;

use PHPUnit\Framework\TestCase;

class ConfigTest extends TestCase
{
    public function testParseFile()
    {
        $config = Config::parseFile(dirname(__DIR__).'/fixtures/PHPTest.PHPHttpServer.config.conf');
        // print_r($config->toArray());
        $this->assertNotNull($config->get('application.tars'));
        // var_export($config->get('application.tars'));
    }
}
