<?php

declare(strict_types=1);

namespace kuiper\rpc\exception;

use kuiper\rpc\transporter\TransporterInterface;

abstract class CommunicationException extends \Exception
{
    /**
     * @var TransporterInterface
     */
    private $connection;

    public function __construct(
        TransporterInterface $connection,
        string $message = null,
        int $code = null,
        \Exception $cause = null)
    {
        parent::__construct($message, $code, $cause);
        $this->connection = $connection;
    }

    /**
     * Gets the connection that generated the exception.
     */
    public function getConnection(): TransporterInterface
    {
        return $this->connection;
    }
}
