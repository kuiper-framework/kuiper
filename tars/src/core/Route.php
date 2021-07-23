<?php

declare(strict_types=1);

namespace kuiper\tars\core;

use Symfony\Component\Validator\Constraints as Assert;

class Route
{
    /**
     * @Assert\NotBlank()
     *
     * @var string
     */
    private $servantName;

    /**
     * @Assert\Count(min=1)
     *
     * @var Endpoint[]
     */
    private $endpoints;

    /**
     * ServantRoute constructor.
     *
     * @param Endpoint[] $endpoints
     */
    public function __construct(string $servantName, array $endpoints)
    {
        $this->servantName = $servantName;
        $this->endpoints = $endpoints;
    }

    public function getServantName(): string
    {
        return $this->servantName;
    }

    public function getSize(): int
    {
        return count($this->endpoints);
    }

    public function isEmpty(): bool
    {
        return empty($this->endpoints);
    }

    /**
     * @return Endpoint[]
     */
    public function getEndpoints(): array
    {
        return $this->endpoints;
    }

    public static function fromString(string $str): Route
    {
        $pos = strpos($str, '@');
        if (false === $pos) {
            throw new \InvalidArgumentException("No servant name in '$str'");
        }
        $servantName = substr($str, 0, $pos);
        $str = substr($str, $pos + 1);

        return new self($servantName, array_map([Endpoint::class, 'fromString'], explode(':', $str)));
    }
}
