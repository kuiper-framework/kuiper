<?php

namespace kuiper\web;

class FastRouteUrlResolverTest extends RouterTestCase
{
    public function createUrlResolver($baseUri = '')
    {
        $app = new RouteRegistrar();
        $app->get('/', 'IndexController:home')->name('home');
        $app->get('/user/{action}', 'UserController:')->name('users');

        return new FastRouteUrlResolver($app, $baseUri);
    }

    public function testResolver()
    {
        $resolver = $this->createUrlResolver();
        $this->assertEquals('/', $resolver->get('home'));
        $this->assertEquals('/user/create', $resolver->get('users', ['action' => 'create']));
    }

    public function testAbsoluteUrl()
    {
        $resolver = $this->createUrlResolver('http://example.com');
        $this->assertEquals('/', $resolver->get('home'));
        $this->assertEquals('http://example.com/', $resolver->get('home', [], true));
        $this->assertEquals('/user/create', $resolver->get('users', ['action' => 'create']));
        $this->assertEquals('http://example.com/user/create', $resolver->get('users', ['action' => 'create'], true));
    }
}
