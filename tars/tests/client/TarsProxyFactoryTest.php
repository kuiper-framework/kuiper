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

namespace kuiper\tars\client;

use GuzzleHttp\Psr7\Response;
use kuiper\rpc\client\RpcClient;
use kuiper\rpc\RpcRequestHandlerInterface;
use kuiper\rpc\transporter\TransporterInterface;
use kuiper\tars\core\TarsRequestInterface;
use kuiper\tars\fixtures\HelloService;
use kuiper\tars\server\TarsServerResponse;
use Laminas\Diactoros\StreamFactory;
use PHPUnit\Framework\TestCase;

class TarsProxyFactoryMockHelper extends TarsProxyFactory
{
    public static $transporter;

    protected function createRpcClient(string $className, array $options): RpcRequestHandlerInterface
    {
        return new RpcClient(self::$transporter, new TarsResponseFactory());
    }
}

class TarsProxyFactoryTest extends TestCase
{
    public function testName()
    {
        TarsProxyFactoryMockHelper::$transporter = \Mockery::mock(TransporterInterface::class);
        TarsProxyFactoryMockHelper::$transporter->shouldReceive('sendRequest')
            ->andReturnUsing(function (TarsRequestInterface $req) {
                error_log('server '.$req->getServantName());

                return new TarsServerResponse($req->withRpcMethod($req
                    ->getRpcMethod()->withResult(['hello world'])), new Response(), new StreamFactory());
            });

        $factory = TarsProxyFactoryMockHelper::createDefault(
            'app.service.HelloObj@tcp -h localhost -p 10001',
            'app.service2.HelloObj@tcp -h localhost -p 10002'
        );
        /** @var HelloService $client */
        $client = $factory->create(HelloService::class, [
            'service' => 'app.service.HelloObj',
        ]);
        $ret = $client->hello('foo');
        var_export($ret);

        $client = $factory->create(HelloService::class, [
            'service' => 'app.service2.HelloObj',
        ]);
        $ret = $client->hello('foo');
        var_export($ret);
    }
}
