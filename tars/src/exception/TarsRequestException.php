<?php

declare(strict_types=1);

namespace kuiper\tars\exception;

use kuiper\rpc\exception\InvalidRequestException;
use kuiper\tars\stream\RequestPacket;

class TarsRequestException extends InvalidRequestException
{
    /**
     * @var RequestPacket
     */
    private $packet;

    /**
     * TarsRequestException constructor.
     */
    public function __construct(RequestPacket $packet, string $message, int $code)
    {
        parent::__construct($message, $code);
        $this->packet = $packet;
    }

    public function getPacket(): RequestPacket
    {
        return $this->packet;
    }
}
