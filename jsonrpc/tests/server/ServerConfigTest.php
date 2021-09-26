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

namespace kuiper\jsonrpc\server;

use GuzzleHttp\Psr7\Utils;
use kuiper\jsonrpc\config\JsonRpcServerConfiguration;
use kuiper\jsonrpc\fixtures\service\CalculatorService;
use kuiper\jsonrpc\TestCase;
use Laminas\Diactoros\ServerRequestFactory;
use Psr\Http\Server\RequestHandlerInterface;

class ServerConfigTest extends TestCase
{
    public function testName()
    {
        $request = (new ServerRequestFactory())->createServerRequest('GET', '/')
            ->withBody(Utils::streamFor(json_encode([
                'jsonrpc' => '2.0',
                'id' => 1,
                'method' => CalculatorService::class.'.add',
                'params' => [1, 2.1],
            ])));
        $response = $this->getContainer()
            ->get(RequestHandlerInterface::class)
            ->handle($request);
        // echo $response->getBody();
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals([
            'jsonrpc' => '2.0',
            'id' => 1,
            'result' => 3.1,
        ], json_decode((string) $response->getBody(), true));
    }

    public function getConfigurations(): array
    {
        return [
            new JsonRpcServerConfiguration(),
        ];
    }
}
