<?php

declare(strict_types=1);

namespace kuiper\db\sharding;

use kuiper\db\sharding\rule\RuleInterface;

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

    public function __construct(string $tableFormat = '%s_%02d')
    {
        $this->tableFormat = $tableFormat;
    }

    public function setDbRule(RuleInterface $rule): void
    {
        $this->dbRule = $rule;
    }

    public function setTableRule(RuleInterface $rule): void
    {
        $this->tableRule = $rule;
    }

    /**
     * {@inheritdoc}
     */
    public function getTable(array $fields, $table): string
    {
        return sprintf($this->tableFormat, $table, $this->tableRule->getPartition($fields));
    }

    /**
     * {@inheritdoc}
     */
    public function getDb(array $fields): int
    {
        return $this->dbRule->getPartition($fields);
    }
}
