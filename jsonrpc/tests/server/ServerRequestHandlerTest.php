<?php

declare(strict_types=1);

namespace kuiper\jsonrpc\server;

use GuzzleHttp\Psr7\Request;
use kuiper\annotations\AnnotationReader;
use kuiper\reflection\ReflectionDocBlockFactory;
use kuiper\rpc\fixtures\User;
use kuiper\rpc\fixtures\UserService;
use kuiper\rpc\server\RpcServerRpcRequestHandler;
use kuiper\serializer\Serializer;
use Laminas\Diactoros\ResponseFactory;
use PHPUnit\Framework\TestCase;

class ServerRequestHandlerTest extends TestCase
{
    public function testName()
    {
        $user = new User();
        $user->setId(1);
        $user->setName('john');

        $userService = \Mockery::mock(UserService::class);
        $userService->shouldReceive('findUser')
            ->with(1)
            ->andReturn($user);
        $userService->shouldReceive('saveUser')
            ->with(\Mockery::capture($savedUser));

        $reflectionDocBlockFactory = new ReflectionDocBlockFactory();
        $normalizer = new Serializer(AnnotationReader::getInstance(), $reflectionDocBlockFactory);
        $handler = new RpcServerRpcRequestHandler([
            UserService::class => $userService,
        ], new JsonRpcServerResponseFactory(new ResponseFactory()));

        $request = new Request('POST', '/', [], json_encode([
            'jsonrpc' => '2.0',
            'id' => 1,
            'method' => 'kuiper.rpc.fixtures.UserService.findUser',
            'params' => [1],
        ]));
        $requestFactory = new JsonRpcServerRequestFactory($normalizer, $reflectionDocBlockFactory);
        $response = $handler->handle($requestFactory->createRequest($request));
        $this->assertEquals('{"jsonrpc":"2.0","id":1,"result":[{"id":1,"name":"john"}]}', (string) $response->getBody());

        $request = new Request('POST', '/', [], json_encode([
            'jsonrpc' => '2.0',
            'id' => 1,
            'method' => 'kuiper.rpc.fixtures.UserService.saveUser',
            'params' => [['id' => 2, 'name' => 'mary']],
        ]));
        $handler->handle($requestFactory->createRequest($request));
        $this->assertNotNull($savedUser);
        $this->assertInstanceOf(User::class, $savedUser);
        $this->assertEquals(2, $savedUser->getId());
    }

    public function testNoOutParam()
    {
        $user = new User();
        $user->setId(1);
        $user->setName('john');

        $userService = \Mockery::mock(UserService::class);
        $userService->shouldReceive('findUser')
            ->with(1)
            ->andReturn($user);
        $userService->shouldReceive('saveUser')
            ->with(\Mockery::capture($savedUser));

        $reflectionDocBlockFactory = new ReflectionDocBlockFactory();
        $normalizer = new Serializer(AnnotationReader::getInstance(), $reflectionDocBlockFactory);
        $handler = new RpcServerRpcRequestHandler([
            UserService::class => $userService,
        ], new NoOutParamJsonRpcServerResponseFactory(new ResponseFactory()));

        $request = new Request('POST', '/', [], json_encode([
            'jsonrpc' => '2.0',
            'id' => 1,
            'method' => 'kuiper.rpc.fixtures.UserService.findUser',
            'params' => [1],
        ]));
        $requestFactory = new JsonRpcServerRequestFactory($normalizer, $reflectionDocBlockFactory);
        $response = $handler->handle($requestFactory->createRequest($request));
        $this->assertEquals('{"jsonrpc":"2.0","id":1,"result":{"id":1,"name":"john"}}', (string) $response->getBody());
    }
}
