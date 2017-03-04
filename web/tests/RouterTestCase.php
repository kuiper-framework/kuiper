<?php

namespace kuiper\web;

use Zend\Diactoros\ServerRequestFactory;

abstract class RouterTestCase extends TestCase
{
    public function createRequest($req)
    {
        list($method, $url) = explode(' ', $req, 2);
        $result = parse_url($url);
        if (isset($result['host'])) {
            $host = $result['host'].(isset($result['port']) ? ':'.$result['port'] : '');
        } else {
            $host = 'localhost';
        }

        return $request = ServerRequestFactory::fromGlobals(
            $server = array_filter([
                'REQUEST_METHOD' => $method,
                'REQUEST_URI' => $result['path'],
                'HTTP_HOST' => $host,
                'HTTPS' => isset($result['scheme']) && $result['scheme'] == 'https' ? 'on' : null,
            ])
        );
    }
}
