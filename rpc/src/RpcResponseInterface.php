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

namespace kuiper\rpc;

use Psr\Http\Message\ResponseInterface;

interface RpcResponseInterface extends ResponseInterface
{
    /**
     * Gets the rpc request object.
     * @return RpcRequestInterface
     */
    public function getRequest(): RpcRequestInterface;
}
