<?php

namespace kuiper\web;

use kuiper\web\fixtures\controllers;

class FastRouteRouterTest extends RouterTestCase
{
    private $namespace = __NAMESPACE__.'\\fixtures\\controllers';

    public function createRouter()
    {
        $app = new MicroApplication($this->getContainer());
        $app->setDefaultNamespace($this->namespace);
        $app->get('/user/{action}', 'IndexController:');

        return $router = new FastRouteRouter(\FastRoute\simpleDispatcher(function ($r) use ($app) {
            foreach ($app->getRoutes() as $route) {
                $r->addRoute($route->getMethods(), $route->getPattern(), $route);
            }
        }));
    }

    /**
     * @dataProvider routes
     */
    public function testDispatch($str, $action, $args = [])
    {
        $router = $this->createRouter();
        $route = $router->dispatch($this->createRequest($str));
        $this->assertEquals([$action, $args], [
            $route->getAction(), $route->getArguments(),
        ]);
    }

    public function routes()
    {
        return [
            ['GET /user/home', [controllers\IndexController::class, 'home']],
        ];
    }
}
