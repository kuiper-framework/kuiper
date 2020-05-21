<?php

declare(strict_types=1);

namespace kuiper\db\sharding;

class Strategy implements StrategyInterface
{
    /**
     * @var string
     */
    private $tableFormat;

    /**
     * @var RuleInterface
     */
    private $dbRule;

    /**
     * @var RuleInterface
     */
    private $tableRule;

    public function __construct($tableFormat = '%s_%02d')
    {
        $this->tableFormat = $tableFormat;
    }

    public function setDbRule(RuleInterface $rule)
    {
        $this->dbRule = $rule;

        return $this;
    }

    public function setTableRule(RuleInterface $rule)
    {
        $this->tableRule = $rule;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getTable(array $fields, $table)
    {
        return sprintf($this->tableFormat, $table, $this->tableRule->getPartition($fields));
    }

    /**
     * {@inheritdoc}
     */
    public function getDb(array $fields)
    {
        return $this->dbRule->getPartition($fields);
    }
}
