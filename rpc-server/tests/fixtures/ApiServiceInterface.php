<?php
namespace kuiper\rpc\server\fixtures;

interface ApiServiceInterface
{
    /**
     * @param Request $request
     * @return Item[]
     */
    public function query(Request $request);
}