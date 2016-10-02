<?php
namespace kuiper\web\middlewares;

use kuiper\test\TestCase;
use kuiper\annotations\AnnotationReader;
use kuiper\web\middlewares\Filter;
use kuiper\di\ContainerBuilder;
use Interop\Container\ContainerInterface;
use Zend\Diactoros\ServerRequestFactory;
use Zend\Diactoros\Response;

class FilterTest extends TestCase
{
    public function createFilter()
    {
        $builder = new ContainerBuilder();
        return new Filter(new AnnotationReader(), $builder->build());
    }

    public function testJson()
    {
        $filter = $this->createFilter();
        $request = ServerRequestFactory::fromGlobals()
                 ->withAttribute('routeInfo', [
                     'controller' => fixtures\IndexController::class,
                     'action' => 'indexAction'
                 ]);
        $response = new Response();
        $called = false;
        $response = $filter($request, $response, function($request, $response) use(&$called){
            $called = true;
            return $response;
        });
        $this->assertTrue($called);
        $this->assertEquals($response->getHeaderLine('Content-Type'), 'application/json');
        // print_r($response);
    }

    /**
     * @expectedException kuiper\web\exception\MethodNotAllowedException
     */
    public function testPost()
    {
        $filter = $this->createFilter();
        $request = ServerRequestFactory::fromGlobals()
                 ->withAttribute('routeInfo', [
                     'controller' => fixtures\IndexController::class,
                     'action' => 'postAction'
                 ]);
        $response = new Response();
        $response = $filter($request, $response, function($request, $response) use(&$called){
            return $response;
        });
    }
}