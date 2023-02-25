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

namespace kuiper\di\attribute;

use Attribute;

#[Attribute(Attribute::TARGET_METHOD)]
final class Bean
{
    public function __construct(private readonly ?string $name = null)
    {
    }

    public function getName(): ?string
    {
        return $this->name;
    }
}