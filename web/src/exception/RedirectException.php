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

namespace kuiper\web\exception;

use InvalidArgumentException;
use RuntimeException;

class RedirectException extends RuntimeException
{
    public function __construct(private readonly string $url, int $code = 302)
    {
        if ($code > 310 || $code < 300) {
            throw new InvalidArgumentException("Invalid redirect http code $code");
        }
        parent::__construct("redirect to $url", $code);
    }

    public function getUrl(): string
    {
        return $this->url;
    }
}
