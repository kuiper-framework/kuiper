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

use kuiper\rpc\RpcResponseInterface;
use kuiper\tars\stream\ResponsePacket;

interface TarsResponseInterface extends RpcResponseInterface
{
    /**
     * @return ResponsePacket
     */
    public function getResponsePacket(): ResponsePacket;
}
