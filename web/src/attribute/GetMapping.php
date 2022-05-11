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

namespace kuiper\web\attribute;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_METHOD)]
final class GetMapping extends RequestMapping
{
    public function __construct(
        string|array $mapping,
        string       $name = '')
    {
        parent::__construct($mapping, $name, ['GET']);
    }
}
