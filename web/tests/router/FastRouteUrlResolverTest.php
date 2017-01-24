<?php
namespace kuiper\web\router;

use kuiper\annotations\AnnotationReader;
use kuiper\reflection\ReflectionNamespaceFactory;
use kuiper\web\FastRouteUrlResolver;
use kuiper\web\RouteScanner;
use kuiper\web\TestCase;
use Zend\Diactoros\Response;
use Zend\Diactoros\ServerRequestFactory;

/**
 * TestCase for Router
 */
class FastRouteUrlResolverTest extends TestCase
{
    public function createUrlResolver()
    {
        $scanner = new RouteScanner(new AnnotationReader(), ReflectionNamespaceFactory::createInstance());
        return new FastRouteUrlResolver($scanner->scan());
    }

    /**
     * @dataProvider uris
     */
    public function testHandle($name, $args, $expected)
    {
        $url = $this->createUrlResolver();
        $this->assertEquals($expected, $url->get($name, $args));
    }

    public function uris()
    {
        return [
            // UserController 没有默认匹配，必须对每个 action 设置 route
            ["user_home", [], "/user/"],
            ["user_edit", ["id" => 10], "/user/edit/10"],
        ];
    }
}
