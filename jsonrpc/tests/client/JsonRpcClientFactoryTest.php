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

namespace kuiper\jsonrpc\client;

use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use kuiper\jsonrpc\config\JsonRpcClientConfiguration;
use kuiper\jsonrpc\core\JsonRpcProtocol;
use kuiper\jsonrpc\fixtures\service\CalculatorService;
use kuiper\jsonrpc\TestCase;

class JsonRpcClientFactoryTest extends TestCase
{
    private $handler;

    private $requests;

    public function testServiceOption()
    {
        $impl = $this->getContainer()->get('calculator');
        $this->handler->append(function ($req) {
            $data = json_decode((string) $req->getBody(), true);

            return new Response(200, [], json_encode([
                'jsonrpc' => JsonRpcProtocol::VERSION,
                'id' => $data['id'],
                'result' => 4.1,
            ]));
        });
        // echo get_class($impl);
        $ret = $impl->add(1, 3.1);
        $this->assertEquals(4.1, $ret);
        $data = json_decode((string) $this->requests[0]['request']->getBody(), true);
        $this->assertEquals('calculator.add', $data['method']);
    }

    protected function getConfigurations(): array
    {
        return [
            new JsonRpcClientConfiguration(),
        ];
    }

    protected function getConfig(): array
    {
        $mock = new MockHandler();
        $this->requests = [];
        $history = Middleware::history($this->requests);
        $handler = HandlerStack::create($mock);
        $handler->push($history);
        $this->handler = $mock;

        return [
            'application' => [
                'jsonrpc' => [
                    'client' => [
                        'clients' => [
                            'calculator' => CalculatorService::class,
                        ],
                        'options' => [
                            'calculator' => [
                                'service' => 'calculator',
                                'protocol' => 'http',
                                'handler' => $handler,
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }
}
