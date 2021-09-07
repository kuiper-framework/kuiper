<?php

declare(strict_types=1);

namespace kuiper\rpc\servicediscovery\loadbalance;

class Equality
{
    /**
     * @var \ArrayIterator
     */
    private $hosts;

    public function __construct(array $hosts)
    {
        $this->hosts = new \ArrayIterator($hosts);
    }

    public function select()
    {
        if (!$this->hosts->valid()) {
            $this->hosts->rewind();
        }
        $value = $this->hosts->current();
        $this->hosts->next();

        return $value;
    }
}
