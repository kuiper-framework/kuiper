<?php

declare(strict_types=1);

namespace kuiper\swoole\pool;

class ConnectionProxy
{
    /**
     * @var PoolInterface
     */
    private $pool;

    /**
     * ConnectionProxy constructor.
     */
    public function __construct(PoolInterface $pool)
    {
        $this->pool = $pool;
    }

    private function getConnection()
    {
        return $this->pool->take();
    }

    public function __get(string $name)
    {
        return $this->getConnection()->{$name};
    }

    public function __set(string $name, $value): void
    {
        $this->getConnection()->{$name} = $value;
    }

    public function __isset($name)
    {
        return isset($this->getConnection()->{$name});
    }

    public function __unset(string $name): void
    {
        unset($this->getConnection()->{$name});
    }

    public function __call(string $name, array $arguments)
    {
        return $this->getConnection()->{$name}(...$arguments);
    }

    public function __invoke(...$arguments)
    {
        /** @var mixed $object */
        $object = $this->getConnection();

        return $object(...$arguments);
    }
}
