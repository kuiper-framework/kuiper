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

namespace kuiper\tars\type;

use kuiper\web\middleware\RemoteAddress;

class StructField
{
    public function __construct(
        private readonly int $tag,
        private readonly string $name,
        private readonly Type $type,
        private readonly bool $required)
    {
    }

    public function getTag(): int
    {
        return $this->tag;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getType(): Type
    {
        return $this->type;
    }

    public function isRequired(): bool
    {
        return $this->required;
    }
}
