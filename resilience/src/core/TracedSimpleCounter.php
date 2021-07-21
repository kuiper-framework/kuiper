<?php

declare(strict_types=1);

namespace kuiper\resilience\core;

class TracedSimpleCounter extends SimpleCounter
{
    /**
     * @var TracedSimpleCounterFactory
     */
    private $factory;

    /**
     * @var string
     */
    private $name;

    /**
     * TracedSimpleCounter constructor.
     */
    public function __construct(TracedSimpleCounterFactory $factory, string $name)
    {
        $this->factory = $factory;
        $this->name = $name;
    }

    public function increment(int $value = 1): int
    {
        $this->factory->trace(implode("\t", [$this->name, __METHOD__, $value]));

        return parent::increment($value);
    }

    public function get(): int
    {
        $this->factory->trace(implode("\t", [$this->name, __METHOD__]));

        return parent::get();
    }

    public function set(int $value): void
    {
        $this->factory->trace(implode("\t", [$this->name, __METHOD__, $value]));
        parent::set($value);
    }

    public function decrement(int $value = 1): int
    {
        $this->factory->trace(implode("\t", [$this->name, __METHOD__, $value]));

        return parent::decrement($value);
    }
}
