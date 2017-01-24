<?php
namespace kuiper\web\router;

use kuiper\annotations\AnnotationReader;
use kuiper\reflection\ReflectionNamespaceFactory;
use kuiper\web\FastRouteRouter as Router;
use kuiper\web\RouteInterface;
use kuiper\web\RouteScanner;
use kuiper\web\TestCase;
use Zend\Diactoros\Response;
use Zend\Diactoros\ServerRequestFactory;

/**
 * TestCase for Router
 */
class FastRouteRouterTest extends TestCase
{
    public function createRouter()
    {
        $router = new Router(\FastRoute\simpleDispatcher(function ($r) {
            $scanner = new RouteScanner(new AnnotationReader(), ReflectionNamespaceFactory::createInstance());
            foreach ($scanner->scan(__NAMESPACE__) as $route) {
                $r->addRoute($route['methods'], $route['pattern'], $route);
            }
        }));
        return $router;
    }

    /**
     * @dataProvider uris
     */
    public function testHandle($method, $uri, $handler, $arguments)
    {
        $_SERVER['REQUEST_METHOD'] = $method;
        $_SERVER['REQUEST_URI'] = $uri;
        $request = ServerRequestFactory::fromGlobals();
        $router = $this->createRouter();
        $route = $router->dispatch($request, new Response);
        // print_r($route);
        $this->assertInstanceOf(RouteInterface::class, $route);
        $this->assertEquals($handler, $route->getHandler());
        $this->assertEquals($arguments, $route->getArguments());
        // print_r($route);
    }

    public function uris()
    {
        return [
            // UserController 没有默认匹配，必须对每个 action 设置 route
            ["GET", "/user", [fixtures\UserController::class, 'indexAction'], []],
            ["GET", "/user/", [fixtures\UserController::class, 'indexAction'], []],
            ["POST", "/user/edit/1", [fixtures\UserController::class, 'editAction'], ['id' => '1']],

            // AppController 设置默认匹配
            ["GET", "/app", [fixtures\AppController::class, 'indexAction'], []],
            ["GET", "/app/list", [fixtures\AppController::class, 'listAction'], []],
            ["GET", "/", [fixtures\IndexController::class, 'indexAction'], []],
        ];
    }

    /**
     * @dataProvider notFoundUris
     * @expectedException \kuiper\web\exception\NotFoundException
     */
    public function testNotFound($method, $uri)
    {
        $_SERVER['REQUEST_METHOD'] = $method;
        $_SERVER['REQUEST_URI'] = $uri;
        $request = ServerRequestFactory::fromGlobals();
        $router = $this->createRouter();
        $route = $router->dispatch($request, new Response);
        // print_r($route);
    }

    public function notFoundUris()
    {
        return [
            ["POST", "/app/edit/1"],
            ["POST", "/user/edit/abc"],
            ["GET", "/user/view/1"],
        ];
    }

    /**
     * @dataProvider methodNotAllowedUris
     * @expectedException \kuiper\web\exception\MethodNotAllowedException
     */
    public function testMethodNotAllowed($method, $uri)
    {
        $_SERVER['REQUEST_METHOD'] = $method;
        $_SERVER['REQUEST_URI'] = $uri;
        $request = ServerRequestFactory::fromGlobals();
        $router = $this->createRouter();
        $route = $router->dispatch($request, new Response);
        // print_r($route);
    }

    public function methodNotAllowedUris()
    {
        return [
            ["POST", "/app/edit"],
            ["GET", "/user/edit/1"],
        ];
    }
}
