<?php

namespace kuiper\web;

use kuiper\web\fixtures\controllers;

class RouterTest extends RouterTestCase
{
    private $namespace = __NAMESPACE__.'\\fixtures\\controllers';

    public function createRouter()
    {
        $app = new RouteRegistrar();
        $app->setDefaultNamespace($this->namespace);
        $app->get('/', 'IndexController:home');
        $app->post('/', 'IndexController:home');
        $app->group(['scheme' => 'http', 'host' => 'example.com'], function ($app) {
            $app->get('/', 'ExampleController:home');
        });
        $app->group(['namespace' => $this->namespace.'\\user'], function ($app) {
            $app->get('/user/info', 'UserController:info');
        });
        $app->group(['scheme' => 'https'], function ($app) {
            $app->get('/', 'HttpsController:home');
        });
        $app->group(['port' => '8080'], function ($app) {
            $app->get('/', 'Port8080Controller:home');
        });
        $app->group(['prefix' => '/blog'], function ($app) {
            $app->get('/', 'BlogController:home');
        });

        $router = new Router($app);

        return $router;
    }

    /**
     * @dataProvider routes
     */
    public function test($str, $action)
    {
        $router = $this->createRouter();
        $route = $router->dispatch($this->createRequest($str));
        // print_r($route);
        $this->assertEquals($action, $route->getAction());
    }

    /**
     * @expectedException \kuiper\web\exception\NotFoundException
     */
    public function testNotFound()
    {
        $router = $this->createRouter();
        $router->dispatch($this->createRequest('GET /no-such-uri'));
    }

    /**
     * @expectedException \kuiper\web\exception\MethodNotAllowedException
     */
    public function testMethodNotAllowed()
    {
        $router = $this->createRouter();
        $router->dispatch($this->createRequest('POST /user/info'));
    }

    public function routes()
    {
        return [
            ['GET /', [controllers\IndexController::class, 'home']],
            ['GET http://example.com/', [controllers\ExampleController::class, 'home']],
            ['GET https://example.com/', [controllers\HttpsController::class, 'home']],
            ['GET http://localhost:8080/', [controllers\Port8080Controller::class, 'home']],
            ['POST https://example.com/', [controllers\IndexController::class, 'home']],
            ['GET /user/info', [controllers\user\UserController::class, 'info']],
            ['GET /blog/', [controllers\BlogController::class, 'home']],
        ];
    }
}
