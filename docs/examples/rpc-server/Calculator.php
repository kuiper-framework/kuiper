<?php

class Calculator implements CalculatorInterface
{
    /**
     * {@inheritdoc}
     */
    public function add($x, $y)
    {
        return $x + $y;
    }

    /**
     * {@inheritdoc}
     */
    public function subtract($x, $y)
    {
        return $x - $y;
    }

    /**
     * {@inheritdoc}
     */
    public function multiply($x, $y)
    {
        return $x * $y;
    }

    /**
     * {@inheritdoc}
     */
    public function divide($x, $y)
    {
        if ($y == 0) {
            throw new InvalidArgumentException('divider cannot be zero');
        }

        return $x / $y;
    }
}
