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
use Psr\Http\Message\ResponseInterface;

class HttpTransporter implements TransporterInterface
{
    /**
     * @var ClientInterface
     */
    private $httpClient;

    /**
     * HttpTransporter constructor.
     */
    public function __construct(ClientInterface $httpClient)
    {
        $this->httpClient = $httpClient;
    }

    public function sendRequest(RequestInterface $request): ResponseInterface
    {
        return $this->httpClient->send($request);
    }
}
