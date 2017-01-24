<?php
namespace kuiper\rpc\server\fixtures;

use InvalidArgumentException;

/**
 * Calculator - sample class to expose via JSON-RPC
 */
class Calculator implements CalculatorInterface
{
    /**
     * @inheritDoc
     */
    public function add($x, $y)
    {
        return $x + $y;
    }

    /**
     * @inheritDoc
     */
    public function subtract($x, $y)
    {
        return $x - $y;
    }

    /**
     * @inheritDoc
     */
    public function multiply($x, $y)
    {
        return $x * $y;
    }

    /**
     * @inheritDoc
     */
    public function divide($x, $y)
    {
        if ($y == 0) {
            throw new InvalidArgumentException("divider cannot be zero");
        }
        return $x / $y;
    }
}
