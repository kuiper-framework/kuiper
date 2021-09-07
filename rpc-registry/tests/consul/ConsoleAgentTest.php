<?php

declare(strict_types=1);

namespace kuiper\rpc\registry\consul;

use GuzzleHttp\Exception\RequestException;
use kuiper\rpc\registry\TestCase;

class ConsoleAgentTest extends TestCase
{
    protected function getConfig(): array
    {
        return [
            'application' => [
                'consul' => [
                    'logging' => true,
                    'log_format' => 'debug',
                ],
            ],
        ];
    }

    public function testName()
    {
        $agent = $this->getContainer()->get(ConsulAgent::class);
        $services = $agent->getServices('Service==TarsRegistry');
        var_export($services);
    }

    public function testRegister()
    {
        $agent = $this->getContainer()->get(ConsulAgent::class);
        $request = new RegisterServiceRequest();
        $request->Name = 'TarsRegistry';
        $request->Address = '10.1.1.165';
        $request->Port = 17890;
        try {
            $agent->registerService($request, null);
        } catch (RequestException $e) {
            if ($e->hasResponse()) {
                echo $e->getResponse()->getBody(), "\n";
            }
        }
    }

    public function testDeregister()
    {
        $agent = $this->getContainer()->get(ConsulAgent::class);
        $agent->deregisterService('TarsRegistry');
    }
}
