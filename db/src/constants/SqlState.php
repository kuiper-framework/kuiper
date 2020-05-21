<?php

declare(strict_types=1);

namespace kuiper\db\constants;

class SqlState
{
    public const INTEGRITY_CONSTRAINT_VIOLATION = '23000';
    public const BAD_TABLE = '42S02';
}
