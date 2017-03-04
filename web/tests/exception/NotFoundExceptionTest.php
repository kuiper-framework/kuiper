<?php

namespace kuiper\web\exception;

use Zend\Diactoros\Response;
use kuiper\web\TestCase;

class NotFoundExceptionTest extends TestCase
{
    public function testResponse()
    {
        $e = new NotFoundException();
        $e->setResponse(new Response());
        $resp = $e->getResponse();
        $this->assertEquals(404, $resp->getStatusCode());
        // print_r($resp);
    }
}
