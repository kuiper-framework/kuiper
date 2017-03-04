<?php
namespace kuiper\web;

use kuiper\web\Application;

class ApplicationTest extends TestCase
{
    public function createApplication()
    {
        return new Application($this->getContainer());
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
