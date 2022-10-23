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

namespace kuiper\db\attribute;

#[\Attribute(\Attribute::TARGET_PROPERTY)]
final class GeneratedValue implements Attribute
{
    public function __construct(private readonly string $type = 'AUTO')
    {
    }

    /**
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }
}
