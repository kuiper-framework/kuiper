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

use GuzzleHttp\Psr7\HttpFactory;
use GuzzleHttp\Psr7\Request;
use kuiper\annotations\AnnotationReader;
use kuiper\jsonrpc\core\JsonRpcProtocol;
use kuiper\reflection\ReflectionDocBlockFactory;
use kuiper\rpc\fixtures\User;
use kuiper\rpc\fixtures\UserService;
use kuiper\rpc\server\RpcServerRpcRequestHandler;
use kuiper\rpc\server\Service;
use kuiper\rpc\ServiceLocator;
use kuiper\serializer\normalizer\ExceptionNormalizer;
use kuiper\serializer\Serializer;
use kuiper\swoole\constants\ServerType;
use kuiper\swoole\ServerPort;
use Laminas\Diactoros\ResponseFactory;
use Laminas\Diactoros\StreamFactory;
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

        $reflectionDocBlockFactory = ReflectionDocBlockFactory::getInstance();
        $normalizer = new Serializer(AnnotationReader::getInstance(), $reflectionDocBlockFactory);
        $services = $this->buildServices([UserService::class => $userService]);
        $handler = new RpcServerRpcRequestHandler(
            $services,
            new JsonRpcServerResponseFactory(new ResponseFactory(), new StreamFactory(), OutParamJsonRpcServerResponse::class),
            new ErrorHandler(new ResponseFactory(), new StreamFactory(), new ExceptionNormalizer())
        );

        $request = new Request('POST', '/', [], json_encode([
            'jsonrpc' => '2.0',
            'id' => 1,
            'method' => 'kuiper.rpc.fixtures.UserService.findUser',
            'params' => [1],
        ]));
        $rpcMethodFactory = new JsonRpcServerMethodFactory($services, $normalizer, $reflectionDocBlockFactory);
        $requestFactory = new JsonRpcServerRequestFactory($rpcMethodFactory);
        $response = $handler->handle($requestFactory->createRequest($request));
        $this->assertEquals('{"jsonrpc":"2.0","id":1,"result":[{"id":1,"name":"john"}]}'.JsonRpcProtocol::EOF, (string) $response->getBody());

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

        $httpFactory = new HttpFactory();

        $userService = \Mockery::mock(UserService::class);
        $userService->shouldReceive('findUser')
            ->with(1)
            ->andReturn($user);
        $userService->shouldReceive('saveUser')
            ->with(\Mockery::capture($savedUser));

        $reflectionDocBlockFactory = ReflectionDocBlockFactory::getInstance();
        $normalizer = new Serializer(AnnotationReader::getInstance(), $reflectionDocBlockFactory);

        $services = $this->buildServices([UserService::class => $userService]);
        $rpcMethodFactory = new JsonRpcServerMethodFactory($services, $normalizer, $reflectionDocBlockFactory);
        $handler = new RpcServerRpcRequestHandler(
            $services,
            new JsonRpcServerResponseFactory($httpFactory, $httpFactory),
            new ErrorHandler($httpFactory, $httpFactory, new ExceptionNormalizer())
        );

        $request = new Request('POST', '/', [], json_encode([
            'jsonrpc' => '2.0',
            'id' => 1,
            'method' => 'kuiper.rpc.fixtures.UserService.findUser',
            'params' => [1],
        ]));
        $requestFactory = new JsonRpcServerRequestFactory($rpcMethodFactory);
        $response = $handler->handle($requestFactory->createRequest($request));
        $this->assertEquals('{"jsonrpc":"2.0","id":1,"result":{"id":1,"name":"john"}}'.JsonRpcProtocol::EOF,
            (string) $response->getBody());
    }

    /**
     * @param array $services
     *
     * @return Service[]
     */
    protected function buildServices(array $services): array
    {
        $ret = [];
        foreach ($services as $name => $service) {
            $locator = new ServiceLocator(str_replace('\\', '.', $name));
            $ret[$locator->getName()] = new Service(
                $locator,
                $service,
                get_class_methods($service),
                new ServerPort('', 0, ServerType::TCP)
            );
        }

        return $ret;
    }
}
