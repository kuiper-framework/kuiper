<?php
namespace kuiper\rpc\client\fixtures;

class Request
{
    /**
     * @var string
     */
    private $query;

    public function getQuery()
    {
        return $this->query;
    }

    public function setQuery($query)
    {
        $this->query = $query;
        return $this;
    }
}