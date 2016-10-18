<?php
namespace kuiper\rpc\server\request;

use kuiper\rpc\server\exception\InvalidRequestException;

abstract class RequestFactory
{
    public static function fromGlobals()
    {
        if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'POST') {
            return self::fromString(file_get_contents('php://input'));
        } else {
            throw new InvalidRequestException("Invalid http method, only 'POST' method supported");
        }
    }

    public static function fromString($body)
    {
        $request = new Request;
        $request->setBody($body);
        return $request;
    }
}
