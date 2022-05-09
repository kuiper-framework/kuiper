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

class Parameter implements ParameterInterface
{
    public function __construct(
        private readonly int $order,
        private readonly string $name,
        private readonly bool $out,
        private readonly Type $type,
        private mixed $data)
    {
    }

    public function getOrder(): int
    {
        return $this->order;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function isOut(): bool
    {
        return $this->out;
    }

    public function getData()
    {
        return $this->data;
    }

    public function withData($data): ParameterInterface
    {
        $new = clone $this;
        $new->data = $data;

        return $new;
    }

    public function getType(): Type
    {
        return $this->type;
    }

    public static function asReturnValue(Type $type): self
    {
        return new self(0, '', false, $type, null);
    }
}
