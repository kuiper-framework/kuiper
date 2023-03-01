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

namespace kuiper\jsonrpc\core;

use kuiper\rpc\HasRequestIdInterface;

interface JsonRpcRequestInterface extends HasRequestIdInterface
{
    public const JSONRPC_VERSION = '2.0';

    /**
     * @return string
     */
    public function getJsonRpcVersion(): string;
}
