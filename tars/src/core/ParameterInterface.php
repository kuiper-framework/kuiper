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

namespace kuiper\tars\core;

use kuiper\tars\type\Type;

interface ParameterInterface
{
    /**
     * Retrieve the parameter position order.
     */
    public function getOrder(): int;

    /**
     * Retrieve the parameter name.
     */
    public function getName(): string;

    /**
     * Whether it's output.
     */
    public function isOut(): bool;

    /**
     * Gets the tar type object.
     */
    public function getType(): Type;

    /**
     * Retrieve the parameter original data.
     *
     * @return mixed
     */
    public function getData();

    /**
     * Create parameter with the value.
     *
     * @param mixed $data
     */
    public function withData($data): ParameterInterface;
}
