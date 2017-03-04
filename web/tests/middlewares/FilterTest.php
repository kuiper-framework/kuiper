<?php

namespace kuiper\web\middlewares;

use kuiper\annotations\AnnotationReader;
use kuiper\di\ContainerBuilder;
use kuiper\web\fixtures\controllers;
use kuiper\web\Route;
use kuiper\web\TestCase;
use Zend\Diactoros\Response;
use Zend\Diactoros\ServerRequestFactory;

class FilterTest extends TestCase
{
    public function createFilter()
    {
        $container = ContainerBuilder::buildDevContainer();

        return $filter = new Filter(new AnnotationReader(), $container);
    }

    public function testJson()
    {
        $filter = $this->createFilter();
        $request = ServerRequestFactory::fromGlobals()
                 ->withAttribute('route', new Route(['GET'], '/', [controllers\FilterController::class, 'index']));
        $called = false;
        $response = $filter($request, new Response(), function ($request, $response) use (&$called) {
            $called = true;

            return $response;
        });
        $this->assertTrue($called);
        $this->assertEquals($response->getHeaderLine('Content-Type'), 'application/json');
        // print_r($response);
    }

    /**
     * @expectedException \kuiper\web\exception\MethodNotAllowedException
     */
    public function testPost()
    {
        $filter = $this->createFilter();
        $request = ServerRequestFactory::fromGlobals()
                 ->withAttribute('route', new Route(['GET'], '/', [controllers\FilterController::class, 'post']));
        $response = $filter($request, new Response(), function ($request, $response) use (&$called) {
            return $response;
        });
    }
}
