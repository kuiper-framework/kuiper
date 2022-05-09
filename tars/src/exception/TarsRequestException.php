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

namespace kuiper\tars\exception;

use kuiper\tars\stream\RequestPacket;

class TarsRequestException extends \Exception
{
    /**
     * TarsRequestException constructor.
     */
    public function __construct(private readonly RequestPacket $packet, string $message, int $code)
    {
        parent::__construct($message, $code);
    }

    public function getPacket(): RequestPacket
    {
        return $this->packet;
    }
}
