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
use kuiper\event\NullEventDispatcher;
use kuiper\resilience\core\SimpleCounter;
use kuiper\resilience\core\SimpleCounterFactory;
use kuiper\resilience\retry\RetryFactoryImpl;
use kuiper\rpc\client\middleware\Retry;
use kuiper\rpc\client\middleware\ServiceDiscovery;
use kuiper\rpc\client\RequestIdGenerator;
use kuiper\rpc\client\RpcClient;
use kuiper\rpc\client\RpcExecutorFactory;
use kuiper\rpc\exception\ConnectFailedException;
use kuiper\rpc\exception\ServerException;
use kuiper\rpc\RpcRequestInterface;
use kuiper\rpc\server\middleware\AccessLog;
use kuiper\rpc\servicediscovery\ServiceEndpoint;
use kuiper\rpc\servicediscovery\ServiceResolverInterface;
use kuiper\rpc\ServiceLocator;
use kuiper\rpc\transporter\Endpoint;
use kuiper\rpc\transporter\TransporterInterface;
use kuiper\swoole\pool\PoolFactory;
use kuiper\tars\core\TarsMethodFactory;
use kuiper\tars\core\TarsRequestInterface;
use kuiper\tars\core\TarsRequestLogFormatter;
use kuiper\tars\fixtures\HelloService;
use kuiper\tars\stream\RequestPacket;
use kuiper\tars\stream\ResponsePacket;
use kuiper\tars\stream\TarsOutputStream;
use kuiper\tars\type\MapType;
use kuiper\tars\type\PrimitiveType;
use Laminas\Diactoros\RequestFactory;
use Laminas\Diactoros\StreamFactory;
use Monolog\Handler\TestHandler;
use Monolog\Logger;
use PHPUnit\Framework\TestCase;

class TarsClientTest extends TestCase
{
    /**
     * @var string
     */
    private $serviceClass;

    /**
     * @var array
     */
    private $responses;

    /**
     * @var array
     */
    private $requests;

    protected function setUp(): void
    {
        $this->responses = [];
        $proxyGenerator = new TarsProxyGenerator();
        $generatedClass = $proxyGenerator->generate(HelloService::class);
        $generatedClass->eval();
        $this->serviceClass = $generatedClass->getClassName();
    }

    private function createClient(array $middlewares = []): HelloService
    {
        $transporter = \Mockery::mock(TransporterInterface::class);
        $transporter->shouldReceive('sendRequest')
            ->andReturnUsing(function (RpcRequestInterface $request) use ($transporter) {
                $response = array_shift($this->responses);
                if ($response instanceof ResponsePacket) {
                    $packet = $response;
                } elseif (is_callable($response)) {
                    $packet = $response($request, $transporter);
                } else {
                    throw new \InvalidArgumentException('invalid response');
                }
                $this->requests[] = ['request' => $request, 'response' => $packet];

                return new TarsResponse($request, new Response(200, [], (string) $packet->encode()), $packet);
            });

        $methodFactory = new TarsMethodFactory();
        $requestFactory = new TarsRequestFactory(
            new RequestFactory(),
            new StreamFactory(),
            $methodFactory,
            new RequestIdGenerator(new SimpleCounter(), 0),
            ''
        );
        $responseFactory = new TarsResponseFactory();
        $rpcClient = new RpcClient($transporter, $responseFactory);
        /** @var HelloService $proxy */
        return new $this->serviceClass(new RpcExecutorFactory($requestFactory, $rpcClient, $middlewares));
    }

    public function testRpcCall()
    {
        $this->responses[] = static function (TarsRequest $request): ResponsePacket {
            $packet = new ResponsePacket();
            $packet->iRequestId = $request->getRequestId();
            $packet->sBuffer = TarsOutputStream::pack(MapType::byteArrayMap(), [
                '' => TarsOutputStream::pack(PrimitiveType::string(), 'hello world'),
            ]);

            return $packet;
        };
        $proxy = $this->createClient();
        $result = $proxy->hello('world');
        $this->assertEquals($result, 'hello world');

        /** @var TarsRequestInterface $request */
        $request = $this->requests[0]['request'];
        $packet = RequestPacket::decode((string) $request->getBody());
        $this->assertEquals('app.hello.HelloObj', $packet->sServantName);
        $this->assertEquals('hello', $packet->sFuncName);
    }

    public function testAccessLogNormal()
    {
        $this->responses[] = static function (TarsRequest $request): ResponsePacket {
            $packet = new ResponsePacket();
            $packet->iRequestId = $request->getRequestId();
            $packet->iRet = 0;
            $packet->sBuffer = TarsOutputStream::pack(MapType::byteArrayMap(), [
                '' => TarsOutputStream::pack(PrimitiveType::string(), 'hello world'),
            ]);

            return $packet;
        };
        $handler = new TestHandler();
        $logger = new Logger('test', [$handler]);
        $accessLog = new AccessLog(new TarsRequestLogFormatter(TarsRequestLogFormatter::CLIENT));
        $accessLog->setLogger($logger);
        $proxy = $this->createClient([$accessLog]);
        try {
            $result = $proxy->hello('world');
        } catch (\Exception $e) {
        }
        $this->assertEquals($result, 'hello world');

        /** @var TarsRequestInterface $request */
        $request = $this->requests[0]['request'];
        $packet = RequestPacket::decode((string) $request->getBody());
        $this->assertEquals('app.hello.HelloObj', $packet->sServantName);
        $this->assertEquals('hello', $packet->sFuncName);
        // print_r($handler->getRecords());
        $this->assertCount(1, $handler->getRecords());
    }

    public function testAccessLogError()
    {
        $this->responses[] = static function (TarsRequest $request): ResponsePacket {
            $packet = new ResponsePacket();
            $packet->iRequestId = $request->getRequestId();
            $packet->iRet = 99999;
            $packet->sResultDesc = 'fail to call';
            $packet->sBuffer = TarsOutputStream::pack(MapType::byteArrayMap(), []);

            return $packet;
        };
        $handler = new TestHandler();
        $logger = new Logger('test', [$handler]);
        $accessLog = new AccessLog(new TarsRequestLogFormatter(TarsRequestLogFormatter::CLIENT));
        $accessLog->setLogger($logger);
        $proxy = $this->createClient([$accessLog]);
        try {
            $result = $proxy->hello('world');
        } catch (\Exception $e) {
            $this->assertInstanceOf(ServerException::class, $e);
        }

        $records = $handler->getRecords();
        $this->assertCount(1, $records);
        $this->assertEquals(99999, json_decode($records[0]['message'], false)->status);
    }

    public function testServiceDiscovery()
    {
        $serviceResolver = \Mockery::mock(ServiceResolverInterface::class);
        $serviceResolver->shouldReceive('resolve')
            ->andReturnUsing(function (ServiceLocator $locator) {
                error_log('resolving '.$locator);

                return new ServiceEndpoint($locator, [
                    Endpoint::fromString('http://host1:80'),
                    Endpoint::fromString('http://host2:80'),
                ]);
            });

        $handler = static function (TarsRequest $request, TransporterInterface $transporter): ResponsePacket {
            if ('host1' === $request->getUri()->getHost()) {
                throw new ConnectFailedException($transporter, 'failed to connect '.$request->getUri(), 0);
            }

            $packet = new ResponsePacket();
            $packet->iRequestId = $request->getRequestId();
            $packet->sBuffer = TarsOutputStream::pack(MapType::byteArrayMap(), [
                '' => TarsOutputStream::pack(PrimitiveType::string(), 'hello world'),
            ]);

            return $packet;
        };
        $this->responses = array_fill(0, 10, $handler);

        $proxy = $this->createClient([
            new Retry(new RetryFactoryImpl(new PoolFactory(), new SimpleCounterFactory(), new NullEventDispatcher())),
            new ServiceDiscovery($serviceResolver),
        ]);
        $result = $proxy->hello('world');
        $this->assertEquals($result, 'hello world');
    }
}
