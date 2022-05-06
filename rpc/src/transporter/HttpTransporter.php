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

use GuzzleHttp\ClientInterface;
use Psr\Http\Message\RequestInterface;

class HttpTransporter implements TransporterInterface
{
    public function __construct(private readonly ClientInterface $httpClient)
    {
    }

    public function close(): void
    {
    }

    public function createSession(RequestInterface $request): Session
    {
        return new SimpleSession($this->httpClient->send($request));
    }
}
