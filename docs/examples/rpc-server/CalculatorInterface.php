<?php

interface CalculatorInterface
{
    /**
     * Return sum of two variables.
     *
     * @param int $x
     * @param int $y
     *
     * @return int
     */
    public function add($x, $y);

    /**
     * Return difference of two variables.
     *
     * @param int $x
     * @param int $y
     *
     * @return int
     */
    public function subtract($x, $y);

    /**
     * Return product of two variables.
     *
     * @param int $x
     * @param int $y
     *
     * @return int
     */
    public function multiply($x, $y);

    /**
     * Return the division of two variables.
     *
     * @param int $x
     * @param int $y
     *
     * @return float
     *
     * @throws InvalidArgumentException if divider is zero
     */
    public function divide($x, $y);
}
