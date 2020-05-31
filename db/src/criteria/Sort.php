<?php

declare(strict_types=1);

namespace kuiper\db\criteria;

class Sort
{
    public const ASC = 'ASC';

    public const DESC = 'DESC';

    /**
     * @var string
     */
    private $column;

    /**
     * @var string
     */
    private $direction;

    /**
     * Sort constructor.
     */
    private function __construct(string $column, string $direction = self::ASC)
    {
        $this->column = $column;
        $this->direction = $direction;
    }

    public static function of(string $column, string $direction = self::ASC): Sort
    {
        return new self($column, $direction);
    }

    public static function ascend(string $column): Sort
    {
        return new self($column, self::ASC);
    }

    public static function descend(string $column): Sort
    {
        return new self($column, self::DESC);
    }

    public function getColumn(): string
    {
        return $this->column;
    }

    public function getDirection(): string
    {
        return $this->direction;
    }

    public function __toString()
    {
        return $this->column.(self::ASC === $this->direction ? '' : ' '.$this->direction);
    }
}
