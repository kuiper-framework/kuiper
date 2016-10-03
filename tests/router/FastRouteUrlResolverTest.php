<?php
namespace kuiper\web\router;

use kuiper\web\FastRouteUrlResolver;
use kuiper\web\RouteScanner;
use kuiper\test\TestCase;
use kuiper\annotations\AnnotationReader;
use kuiper\reflection\ClassScanner;
use Zend\Diactoros\ServerRequestFactory;
use Zend\Diactoros\Response;

/**
 * TestCase for Router
 */
class FastRouteUrlResolverTest extends TestCase
{
    public function createUrlResolver()
    {
        $classScanner = new ClassScanner;
        $classScanner->register(__NAMESPACE__, __DIR__);
        $scanner = new RouteScanner(new AnnotationReader(), $classScanner);
        return new FastRouteUrlResolver($scanner->scan(__NAMESPACE__));
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
