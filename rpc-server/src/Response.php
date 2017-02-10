<?php

namespace kuiper\rpc\server;

use function GuzzleHttp\Psr7\stream_for;

class Response implements ResponseInterface
{
    use MessageTrait;

    /**
     * @var mixed
     */
    private $result;

    public function __construct($body = '')
    {
        $this->stream = stream_for($body);
    }

    /**
     * {@inheritdoc}
     */
    public function getResult()
    {
        return $this->result;
    }

    /**
     * {@inheritdoc}
     */
    public function withResult($result)
    {
        $new = clone $this;
        $new->result = $result;

        return $new;
    }
}
