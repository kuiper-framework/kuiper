<?php

namespace kuiper\web\exception;

use Zend\Diactoros\Response;
use kuiper\web\TestCase;

class HttpExceptionTest extends TestCase
{
    public function testResponse()
    {
        $e = new HttpException();
        $e->setResponse(new Response())
            ->setStatusCode(400);
        $resp = $e->getResponse();
        $this->assertEquals(400, $resp->getStatusCode());
        // print_r($resp);
    }
}
