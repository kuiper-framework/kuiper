<?php

namespace kuiper\web\exception;

use kuiper\web\TestCase;

class NotFoundExceptionTest extends TestCase
{
    public function testResponse()
    {
        $e = new NotFoundException();
        $this->assertEquals(404, $e->getStatusCode());
        // print_r($resp);
    }
}
