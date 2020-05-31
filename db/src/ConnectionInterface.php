<?php

declare(strict_types=1);

namespace kuiper\db;

use PDO;

interface ConnectionInterface extends PdoInterface
{
    /**
     * Connects to the database and sets PDO attributes.
     *
     * @throws \PDOException if the connection fails
     */
    public function connect(): void;

    /**
     * Explicitly disconnect by unset the PDO instance; does not prevent
     * later reconnection, whether implicit or explicit.
     */
    public function disconnect(): void;

    /**
     * Returns the underlying PDO connection object.
     */
    public function getPdo(): PDO;
}
