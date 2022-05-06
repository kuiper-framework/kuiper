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

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\HttpFactory;
use GuzzleHttp\Psr7\Response;
use kuiper\jsonrpc\core\JsonRpcRequestInterface;
use kuiper\reflection\ReflectionDocBlockFactory;
use kuiper\resilience\core\SimpleCounter;
use kuiper\rpc\client\ProxyGenerator;
use kuiper\rpc\client\RequestIdGenerator;
use kuiper\rpc\client\RpcClient;
use kuiper\rpc\client\RpcExecutorFactory;
use kuiper\rpc\client\RpcResponseFactoryInterface;
use kuiper\rpc\client\RpcResponseNormalizer;
use kuiper\rpc\fixtures\HelloService;
use kuiper\rpc\fixtures\User;
use kuiper\rpc\fixtures\UserService;
use kuiper\rpc\transporter\HttpTransporter;
use kuiper\serializer\normalizer\ExceptionNormalizer;
use kuiper\serializer\Serializer;
use Laminas\Diactoros\RequestFactory;
use PHPUnit\Framework\TestCase;

class JsonRpcClientTest extends TestCase
{
    /**
     * @var MockHandler
     */
    private $mockHandler;

    /**
     * @var array
     */
    private $requests;

    private function createClient(string $clientInterface, RpcResponseFactoryInterface $responseFactory = null)
    {
        $proxyGenerator = new ProxyGenerator();
        $generatedClass = $proxyGenerator->generate($clientInterface);
        $generatedClass->eval();
        $class = $generatedClass->getClassName();
        $mock = new MockHandler();
        $requests = [];
        $history = Middleware::history($requests);
        $handlerStack = HandlerStack::create($mock);
        $handlerStack->push($history);
        $client = new Client(['handler' => $handlerStack]);

        $transporter = new HttpTransporter($client);
        $rpcMethodFactory = new JsonRpcMethodFactory();
        $httpFactory = new HttpFactory();
        $requestFactory = new JsonRpcRequestFactory(new RequestFactory(), $httpFactory, $rpcMethodFactory, new RequestIdGenerator(new SimpleCounter(), 0), '/');
        if (null === $responseFactory) {
            $responseFactory = new SimpleJsonRpcResponseFactory();
        }
        $rpcClient = new RpcClient($transporter, $responseFactory);
        $rpcExecutorFactory = new RpcExecutorFactory($requestFactory, $rpcClient);

        $this->mockHandler = $mock;
        $this->requests = &$requests;

        return new $class($rpcExecutorFactory);
    }

    public function testSimple()
    {
        $proxy = $this->createClient(HelloService::class);
        $this->mockHandler->append(
            new Response(200, [], json_encode([
                'jsonrpc' => JsonRpcRequestInterface::JSONRPC_VERSION,
                'id' => 1,
                'result' => ['hello world'],
            ]))
        );
        $result = $proxy->hello('world');
        $this->assertEquals($result, 'hello world');
        $request = json_decode((string) $this->requests[0]['request']->getBody(), true);
        $this->assertEquals('kuiper.rpc.fixtures.HelloService.hello', $request['method']);
        $this->assertEquals(['world'], $request['params']);
        // echo $request->getBody();
    }

    public function testNormalizer()
    {
        $reflectionDocBlockFactory = ReflectionDocBlockFactory::getInstance();
        $normalizer = new Serializer($reflectionDocBlockFactory);
        $responseFactory = new JsonRpcResponseFactory(new RpcResponseNormalizer($normalizer, $reflectionDocBlockFactory), new ExceptionNormalizer());
        $proxy = $this->createClient(UserService::class, $responseFactory);
        $user = new User();
        $user->setId(1);
        $user->setName('john');

        $this->mockHandler->append(
            new Response(200, [], json_encode([
                'jsonrpc' => JsonRpcRequestInterface::JSONRPC_VERSION,
                'id' => 1,
                'result' => [$user],
            ])),
            new Response(200, [], json_encode([
                'jsonrpc' => JsonRpcRequestInterface::JSONRPC_VERSION,
                'id' => 2,
                'result' => [[$user], 2],
            ])),
            new Response(200, [], json_encode([
                'jsonrpc' => JsonRpcRequestInterface::JSONRPC_VERSION,
                'id' => 3,
                'result' => [null],
            ]))
        );

        /** @var UserService $proxy */
        $result = $proxy->findUser(1);
        $this->assertInstanceOf(User::class, $result);

        $request = json_decode((string) $this->requests[0]['request']->getBody(), true);
        $this->assertEquals('kuiper.rpc.fixtures.UserService.findUser', $request['method']);
        $this->assertEquals([1], $request['params']);

        $users = $proxy->findAllUser($total);
        $this->assertEquals(2, $total);
        $this->assertCount(1, $users);

        $user = new User();
        $user->setId(2);
        $user->setName('mary');
        $proxy->saveUser($user);
        $request = json_decode((string) $this->requests[2]['request']->getBody(), true);
        // print_r($request);
        $this->assertEquals('kuiper.rpc.fixtures.UserService.saveUser', $request['method']);
        // echo $request->getBody();
    }

    public function testNoOutParam()
    {
        $reflectionDocBlockFactory = ReflectionDocBlockFactory::getInstance();
        $normalizer = new Serializer($reflectionDocBlockFactory);
        $responseFactory = new NoOutParamJsonRpcResponseFactory(new RpcResponseNormalizer($normalizer, $reflectionDocBlockFactory), new ExceptionNormalizer());
        /** @var UserService $proxy */
        $proxy = $this->createClient(UserService::class, $responseFactory);

        $user = new User();
        $user->setId(1);
        $user->setName('john');

        $this->mockHandler->append(
            new Response(200, [], json_encode([
                'jsonrpc' => JsonRpcRequestInterface::JSONRPC_VERSION,
                'id' => 1,
                'result' => $user,
            ]))
        );
        $result = $proxy->findUser(1);
        $this->assertInstanceOf(User::class, $result);

        $request = json_decode((string) $this->requests[0]['request']->getBody(), true);
        $this->assertEquals('kuiper.rpc.fixtures.UserService.findUser', $request['method']);
        $this->assertEquals([1], $request['params']);

        // echo $request->getBody();
    }
}
