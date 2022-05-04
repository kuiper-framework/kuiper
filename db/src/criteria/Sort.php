<?php

/*
 * This file is part of the Kuiper package.
 *
 * (c) Ye Wenbin <wenbinye@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace kuiper\db\criteria;

class Sort
{
    public const ASC = 'ASC';

    public const DESC = 'DESC';

    /**
     * Sort constructor.
     */
    private function __construct(
        private readonly string $column,
        private readonly string $direction = self::ASC)
    {
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
