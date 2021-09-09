<?php

declare(strict_types=1);

namespace kuiper\tars\server;

use GuzzleHttp\Psr7\HttpFactory;
use kuiper\annotations\AnnotationReader;
use kuiper\rpc\server\RpcServerRpcRequestHandler;
use kuiper\rpc\server\Service;
use kuiper\rpc\ServiceLocator;
use kuiper\swoole\ServerPort;
use kuiper\tars\core\EndpointParser;
use kuiper\tars\fixtures\User;
use kuiper\tars\fixtures\UserServant;
use kuiper\tars\stream\RequestPacket;
use kuiper\tars\stream\TarsOutputStream;
use kuiper\tars\type\MapType;
use kuiper\tars\type\PrimitiveType;
use PHPUnit\Framework\TestCase;

class TarsServerRpcRequestHandlerTest extends TestCase
{
    public function testName()
    {
        $user = new User();
        $user->name = 'john';
        $userServant = \Mockery::mock(UserServant::class);
        $userServant->shouldReceive('findAllUser')
            ->andReturnUsing(function (&$total) use ($user) {
                $total = 3;

                return [$user];
            });
        $services = $this->createServices([
            'PHPDemo.PHPTcpServer.UserObj' => $userServant,
        ]);
        $serverProperties = new ServerProperties();
        $serverProperties->setApp('PHPDemo');
        $serverProperties->setServer('PHPTcpServer');
        $adapter = new Adapter();
        $adapter->setEndpoint(EndpointParser::parse('tcp -h 127.0.0.1 -p 8003 -t 60000'));
        $adapter->setServant('PHPDemo.PHPTcpServer.UserObj');
        $serverProperties->setAdapters([$adapter]);
        $httpFactory = new HttpFactory();
        $responseFactory = new TarsServerResponseFactory($httpFactory, $httpFactory);
        $handler = new RpcServerRpcRequestHandler($services, $responseFactory, []);
        $rpcMethodFactory = new TarsServerMethodFactory($serverProperties, $services, AnnotationReader::getInstance());
        $requestFactory = new TarsServerRequestFactory($serverProperties, $rpcMethodFactory);
        $httpRequest = $httpFactory->createRequest('GET', 'tcp://localhost:8003');
        $requestPacket = new RequestPacket();
        $requestPacket->sServantName = 'PHPDemo.PHPTcpServer.UserObj';
        $requestPacket->sFuncName = 'findAllUser';
        $requestPacket->sBuffer = TarsOutputStream::pack(MapType::byteArrayMap(), [
            'total' => TarsOutputStream::pack(PrimitiveType::int(), 0),
        ]);
        $httpRequest->getBody()->write((string) $requestPacket->encode());
        $request = $requestFactory->createRequest($httpRequest);
        $response = $handler->handle($request);
        // var_export($response->getRequest()->getRpcMethod()->getResult());
        $this->assertNotEmpty($response->getRequest()->getRpcMethod()->getResult());
    }

    private function createServices(array $services): array
    {
        $ret = [];
        foreach ($services as $name => $impl) {
            $ret[$name] = new Service(
                new ServiceLocator($name),
                $impl,
                [],
                new ServerPort('', 0, 'tcp')
            );
        }

        return $ret;
    }
}
