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

    /**
     * @return mixed
     *
     * @throws \kuiper\swoole\exception\PoolTimeoutException
     */
    private function getConnection()
    {
        return $this->pool->take();
    }

    /**
     * @return mixed
     *
     * @throws \kuiper\swoole\exception\PoolTimeoutException
     */
    public function __get(string $name)
    {
        /* @phpstan-ignore-next-line */
        return $this->getConnection()->{$name};
    }

    /**
     * @param mixed $value
     *
     * @throws \kuiper\swoole\exception\PoolTimeoutException
     */
    public function __set(string $name, $value): void
    {
        /* @phpstan-ignore-next-line */
        $this->getConnection()->{$name} = $value;
    }

    public function __isset(string $name): bool
    {
        /* @phpstan-ignore-next-line */
        return isset($this->getConnection()->{$name});
    }

    public function __unset(string $name): void
    {
        /* @phpstan-ignore-next-line */
        unset($this->getConnection()->{$name});
    }

    /**
     * @return mixed
     *
     * @throws \kuiper\swoole\exception\PoolTimeoutException
     */
    public function __call(string $name, array $arguments)
    {
        /* @phpstan-ignore-next-line */
        return $this->getConnection()->{$name}(...$arguments);
    }

    /**
     * @param array ...$arguments
     *
     * @return mixed
     *
     * @throws \kuiper\swoole\exception\PoolTimeoutException
     */
    public function __invoke(...$arguments)
    {
        /** @var mixed $object */
        $object = $this->getConnection();

        return $object(...$arguments);
    }
}
