<?php

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
    public function send(RequestInterface $request): ResponseInterface;
}
