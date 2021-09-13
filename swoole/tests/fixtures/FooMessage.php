<?php

declare(strict_types=1);

namespace kuiper\swoole\fixtures;

use kuiper\swoole\event\MessageInterface;

class FooMessage implements MessageInterface
{
    /**
     * @var string
     */
    private $name;

    /**
     * FooMessage constructor.
     *
     * @param $name
     */
    public function __construct($name)
    {
        $this->name = $name;
    }

    /**
     * @return mixed
     */
    public function getName()
    {
        return $this->name;
    }
}
