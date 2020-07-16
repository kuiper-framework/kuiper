<?php

declare(strict_types=1);

namespace kuiper\db\sharding\rule;

class EqualToRule implements RuleInterface
{
    /**
     * @var string
     */
    private $field;

    public function __construct($field)
    {
        $this->field = $field;
    }

    public function getPartition(array $fields)
    {
        return $fields[$this->field];
    }
}
