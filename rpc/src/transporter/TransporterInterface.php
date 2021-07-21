<?php

declare(strict_types=1);

namespace kuiper\rpc\transporter;

use kuiper\rpc\exception\CommunicationException;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

interface TransporterInterface
{
    /**
     * @throws CommunicationException
     */
    public function send(RequestInterface $request): ResponseInterface;

    public function recv(): ResponseInterface;
}
