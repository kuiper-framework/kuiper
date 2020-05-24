<?php

declare(strict_types=1);

namespace kuiper\db\sharding\rule;

class IdentityRule implements RuleInterface
{
    /**
     * @var int
     */
    private $id;

    public function __construct($id)
    {
        $this->id = $id;
    }

    public function getPartition(array $fields)
    {
        return $this->id;
    }
}
