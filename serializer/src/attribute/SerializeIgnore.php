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

namespace kuiper\serializer\attribute;


use Attribute;

/**
 * Mark field not serialize.
 */
#[Attribute(Attribute::TARGET_METHOD| Attribute::TARGET_PROPERTY)]
final class SerializeIgnore
{
}
