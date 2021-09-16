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

namespace kuiper\rpc\transporter;

use kuiper\rpc\exception\CommunicationException;
use kuiper\rpc\exception\InvalidRequestException;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

interface TransporterInterface
{
    /**
     * @throws CommunicationException
     * @throws InvalidRequestException
     */
    public function sendRequest(RequestInterface $request): ResponseInterface;
}
