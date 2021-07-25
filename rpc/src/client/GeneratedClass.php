<?php

declare(strict_types=1);

namespace kuiper\rpc\client;

class GeneratedClass
{
    /**
     * @var string
     */
    private $className;

    /**
     * @var string
     */
    private $code;

    /**
     * GeneratedClass constructor.
     */
    public function __construct(string $className, string $code)
    {
        $this->className = $className;
        $this->code = $code;
    }

    public function getClassName(): string
    {
        return $this->className;
    }

    public function getCode(): string
    {
        return $this->code;
    }

    public function save(string $fileName): void
    {
        file_put_contents($fileName, $this->code);
    }

    public function eval(): void
    {
        if (class_exists($this->className)) {
            return;
        }
        eval($this->code);
    }
}
