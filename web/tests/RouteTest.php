<?php

namespace kuiper\web;

use Interop\Container\ContainerInterface;
use kuiper\web\fixtures\controllers;
use Mockery;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Zend\Diactoros\Response;
use Zend\Diactoros\ServerRequestFactory;

class RouteTest extends TestCase
{
    public function createRoute($action)
    {
        $route = new Route(['GET'], '/', $action);
        $route->setContainer($this->getContainer());

        return $route;
    }

    public function testRunClosureReturnNothing()
    {
        $self = $this;
        $run = false;
        $route = $this->createRoute($action = function ($req, $resp, array $args) use (&$run, $self) {
            $self->assertInstanceOf(ContainerInterface::class, $this);
            $self->assertInstanceOf(ServerRequestInterface::class, $req);
            $self->assertInstanceOf(ResponseInterface::class, $resp);
            $self->assertEmpty($args);
            $run = true;
        });
        $result = $route->run(ServerRequestFactory::fromGlobals(), $response = new Response());
        $this->assertTrue($run);
        $this->assertSame($response, $result);
        // print_r($result);
    }

    public function testRunClosureReturnResponse()
    {
        $route = $this->createRoute($action = function () {
            return new Response();
        });
        $result = $route->run(ServerRequestFactory::fromGlobals(), $response = new Response());
        $this->assertNotSame($response, $result);
        $this->assertInstanceOf(ResponseInterface::class, $result);
    }

    public function testControllerWithNoArgs()
    {
        $route = $this->createRoute($action = [controllers\KuiperController::class, 'home']);
        $this->container->shouldReceive('get')
            ->with(controllers\KuiperController::class)
            ->andReturn($controller = Mockery::mock(new controllers\KuiperController()));
        $controller->shouldReceive('home')
            ->once();
        $result = $route->run($request = ServerRequestFactory::fromGlobals(), $response = new Response());
        $this->assertSame($result, $response);
        $this->assertSame($request, $controller->getRequest());
        $this->assertSame($response, $controller->getResponse());
    }

    public function testControllerWithArgs()
    {
        $route = $this->createRoute($action = [controllers\KuiperController::class, 'home']);
        $route = $route->withArguments(['userId' => 1]);
        $this->container->shouldReceive('get')
            ->with(controllers\KuiperController::class)
            ->andReturn($controller = Mockery::mock(new controllers\KuiperController()));
        $controller->shouldReceive('home')
            ->once()
            ->with(1)
            ->andReturn(new Response());
        $result = $route->run(ServerRequestFactory::fromGlobals(), $response = new Response());
        $this->assertNotSame($result, $response);
        $this->assertInstanceOf(ResponseInterface::class, $result);
    }
}
