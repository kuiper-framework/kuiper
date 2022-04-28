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

#[Attribute(Attribute::TARGET_CLASS)]
final class ComponentScan
{
    /**
     * @param string[] $basePackages
     */
    public function __construct(private array $basePackages = [])
    {
    }

    public function getBasePackages(): array
    {
        return $this->basePackages;
    }
}
