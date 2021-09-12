<?php

declare(strict_types=1);

namespace kuiper\web\session;

use kuiper\web\TestCase;
use Laminas\Diactoros\Response;
use Laminas\Diactoros\ServerRequestFactory;
use Symfony\Component\Cache\Adapter\ArrayAdapter;

class CacheStoreSessionTest extends TestCase
{
    public function testName()
    {
        $cache = new ArrayAdapter();
        $factory = new CacheStoreSessionFactory(new CacheSessionHandler($cache));
        $session = $factory->create(ServerRequestFactory::fromGlobals());
        $session->start();
        $response = $session->setCookie(new Response());
        print_r($response->getHeaders());
    }
}
