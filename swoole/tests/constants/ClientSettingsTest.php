<?php


namespace kuiper\swoole\constants;



use PHPUnit\Framework\TestCase;

class ClientSettingsTest extends TestCase
{
    public function testName(): void
    {
        $settings = ClientSettings::OPEN_EOF_CHECK;
        $this->assertEquals('bool', $settings->type());
    }
}