<?php
namespace kuiper\rpc\client;

use Symfony\Component\EventDispatcher\Event;

class RequestEvent extends Event
{
    /**
     * @var AbstractJsonClient
     */
    private $client;

    /**
     * @var string
     */
    private $request;

    /**
     * @var string
     */
    private $response;
    
    public function __construct($client, $request)
    {
        $this->client = $client;
        $this->request = $request;
    }

    public function getRequest()
    {
        return $this->request;
    }

    public function setRespone($response)
    {
        $this->response = $response;
        return $this;
    }

    public function getResponse()
    {
        return $this->response;
    }

    public function hasResponse()
    {
        return isset($this->response);
    }
}
