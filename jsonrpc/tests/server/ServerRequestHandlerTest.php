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

use GuzzleHttp\Psr7\ServerRequest;
use kuiper\helper\Arrays;
use kuiper\jsonrpc\core\JsonRpcProtocol;
use kuiper\reflection\ReflectionDocBlockFactory;
use kuiper\rpc\fixtures\User;
use kuiper\rpc\fixtures\UserService;
use kuiper\rpc\server\RpcServerRpcRequestHandler;
use kuiper\rpc\server\Service;
use kuiper\rpc\ServiceLocatorImpl;
use kuiper\serializer\Serializer;
use kuiper\swoole\constants\ServerType;
use kuiper\swoole\ServerPort;
use Laminas\Diactoros\ResponseFactory;
use Laminas\Diactoros\StreamFactory;
use Mockery;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionMethod;

class ServerRequestHandlerTest extends TestCase
{
    public function testName()
    {
        $user = new User();
        $user->setId(1);
        $user->setName('john');

        $userService = Mockery::mock(UserService::class);
        $userService->shouldReceive('findUser')
            ->with(1)
            ->andReturn($user);
        $userService->shouldReceive('saveUser')
            ->with(Mockery::capture($savedUser));

        $reflectionDocBlockFactory = ReflectionDocBlockFactory::getInstance();
        $normalizer = new Serializer($reflectionDocBlockFactory);
        $services = $this->buildServices([UserService::class => $userService]);
        $handler = new RpcServerRpcRequestHandler(
            $services,
            new JsonRpcServerResponseFactory(new ResponseFactory(), new StreamFactory())
        );

        $request = new ServerRequest('POST', '/', [], json_encode([
            'jsonrpc' => '2.0',
            'id' => 1,
            'method' => 'kuiper.rpc.fixtures.UserService.findUser',
            'params' => [1],
        ]));
        $rpcMethodFactory = new JsonRpcServerMethodFactory($services, $normalizer, $reflectionDocBlockFactory);
        $requestFactory = new JsonRpcServerRequestFactory($rpcMethodFactory);
        $response = $handler->handle($requestFactory->createRequest($request));
        $this->assertEquals('{"@extended":true,"jsonrpc":"2.0","id":1,"result":[{"id":1,"name":"john"}]}'.JsonRpcProtocol::EOF, (string) $response->getBody());

        $request = new ServerRequest('POST', '/', [], json_encode([
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

    /**
     * @param array $services
     *
     * @return Service[]
     */
    protected function buildServices(array $services): array
    {
        $ret = [];
        foreach ($services as $name => $service) {
            $class = new ReflectionClass($name);
            $locator = new ServiceLocatorImpl(str_replace('\\', '.', $name));
            $ret[$locator->getName()] = new Service(
                $locator,
                $service,
                Arrays::assoc($class->getMethods(ReflectionMethod::IS_PUBLIC), 'name'),
                new ServerPort('', 0, ServerType::TCP)
            );
        }

        return $ret;
    }
}
