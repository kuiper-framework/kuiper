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

namespace kuiper\rpc\registry\consul;

use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7\Response;
use kuiper\rpc\registry\TestCase;

class ConsoleAgentTest extends TestCase
{
    /**
     * @var ClientInterface|\Mockery\LegacyMockInterface|\Mockery\MockInterface
     */
    private $httpClient;

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

    protected function getDefinitions(): array
    {
        return [
            'consulHttpClient' => $this->httpClient = \Mockery::mock(ClientInterface::class),
        ];
    }

    public function testName()
    {
        $this->httpClient->shouldReceive('send')
            ->andReturn(new Response(200, ['content-type' => 'application/json'], '{
  "app.service.UserService@10.1.1.165:8002": {
    "ID": "app.service.UserService@10.1.1.165:8002",
    "Service": "app.service.UserService",
    "Tags": [],
    "Meta": {},
    "Port": 8002,
    "Address": "10.1.1.165",
    "SocketPath": "",
    "TaggedAddresses": {
      "lan_ipv4": {
        "Address": "10.1.1.165",
        "Port": 8002
      },
      "wan_ipv4": {
        "Address": "10.1.1.165",
        "Port": 8002
      }
    },
    "Weights": {
      "Passing": 1,
      "Warning": 1
    },
    "EnableTagOverride": false,
    "Datacenter": "dc1"
  }
}
'));
        $agent = $this->getContainer()->get(ConsulAgent::class);
        $services = $agent->getServices('Service==app.service.UserService');
        // var_export($services);
        $this->assertCount(1, $services);
    }

    public function testRegister()
    {
        $this->httpClient->shouldReceive('send')
            ->withArgs([\Mockery::capture($argRequest)])
            ->andReturn(new Response(200, ['content-type' => 'application/json'], '{}'));
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
        $this->assertEquals([
            'ID' => null,
            'Name' => 'TarsRegistry',
            'Tags' => null,
            'Address' => '10.1.1.165',
            'Port' => 17890,
            'TaggedAddresses' => null,
            'Meta' => null,
            'Kind' => '',
            'EnableTagOverride' => null,
            'Check' => null,
            'Checks' => null,
            'Weights' => null,
        ], json_decode((string) $argRequest->getBody(), true));
    }

    public function testDeregister()
    {
        $this->httpClient->shouldReceive('send')
            ->withArgs([\Mockery::capture($argRequest)])
            ->andReturn(new Response(200, ['content-type' => 'application/json'], '{}'));
        $agent = $this->getContainer()->get(ConsulAgent::class);
        $agent->deregisterService('app.service.CalculatorService');
        $this->assertEquals('/v1/agent/service/deregister/app.service.CalculatorService', (string) $argRequest->getUri());
    }

    public function testServiceHealth()
    {
        $this->httpClient->shouldReceive('send')
            ->withArgs([\Mockery::capture($argRequest)])
            ->andReturn(new Response(200, ['content-type' => 'application/json'],
                file_get_contents(__DIR__.'/../fixtures/service_health.json')));
        $agent = $this->getContainer()->get(ConsulAgent::class);
        $services = $agent->getServiceHealth('app.service.CalculatorService');
        // var_export($services);
        $this->assertCount(1, $services);
    }
}
