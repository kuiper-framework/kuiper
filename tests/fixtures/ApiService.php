<?php
namespace kuiper\rpc\server\fixtures;

use InvalidArgumentException;

class ApiService implements ApiServiceInterface
{
    /**
     * @inheritDoc
     */
    public function query(Request $request)
    {
        if ($request->getQuery() === null) {
            throw new InvalidArgumentException("invalid query");
        }
        $item = new Item;
        $item->setName($request->getQuery());
        return [$item];
    }
}