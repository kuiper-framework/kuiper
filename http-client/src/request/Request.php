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

namespace kuiper\http\client\request;

class Request
{
    public function __construct(private readonly array $options)
    {
    }

    public function getOptions(): array
    {
        return $this->options;
    }
}
