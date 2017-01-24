<?php
namespace kuiper\web;

use Interop\Container\ContainerInterface;
use kuiper\web\Application;

class ApplicationTest extends TestCase
{
    public function createApplication()
    {
        $stub = $this->getMockBuilder(ContainerInterface::class)
            ->getMock();
        return new Application($stub);
    }

    public function testAddMiddlewareBeforeDispatch()
    {
        $app = $this->createApplication();
        $app->add(function () {
        }, 'before:dispatch');
        $middlewares = $this->readAttribute($app, 'middlewares');
        // print_r($middlewares);
        $this->assertArrayHasKey(Application::DISPATCH, $middlewares);
    }

    public function testAddMiddlewareAfterRoute()
    {
        $app = $this->createApplication();
        $app->add(function () {
        }, 'after:route');
        $middlewares = $this->readAttribute($app, 'middlewares');
        // print_r($middlewares);
        $this->assertArrayHasKey(Application::DISPATCH, $middlewares);
    }

    public function testAddMiddlewareUseConstant()
    {
        $app = $this->createApplication();
        $app->add(function () {
        }, Application::DISPATCH);
        $middlewares = $this->readAttribute($app, 'middlewares');
        // print_r($middlewares);
        $this->assertArrayHasKey(Application::DISPATCH, $middlewares);
    }
}
