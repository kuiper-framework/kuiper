<?php

declare(strict_types=1);

namespace kuiper\swoole\pool;

class Connection
{
    /**
     * @var int
     */
    public $id;

    /**
     * @var mixed
     */
    public $conn;

    /**
     * Connection constructor.
     *
     * @param int $id
     */
    public function __construct(int $id)
    {
        $this->id = $id;
    }
}
