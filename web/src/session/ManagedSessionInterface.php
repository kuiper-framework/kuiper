<?php

namespace kuiper\web\session;

use kuiper\web\RequestAwareInterface;
use Psr\Http\Message\ResponseInterface;

interface ManagedSessionInterface extends SessionInterface, RequestAwareInterface
{
    /**
     * @param ResponseInterface $response
     *
     * @return ResponseInterface
     */
    public function respond(ResponseInterface $response);
}
