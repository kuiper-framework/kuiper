<?php

namespace kuiper\serializer\fixtures;

class Collection
{
    /**
     * @var int
     */
    private $total;

    /**
     * @var object[]
     */
    private $items;

    /**
     * @return int
     */
    public function getTotal()
    {
        return $this->total;
    }

    /**
     * @param int $total
     *
     * @return Collection
     */
    public function setTotal($total)
    {
        $this->total = $total;

        return $this;
    }

    /**
     * @return object[]
     */
    public function getItems()
    {
        return $this->items;
    }

    /**
     * @param object[] $items
     *
     * @return Collection
     */
    public function setItems(array $items)
    {
        $this->items = $items;

        return $this;
    }
}
